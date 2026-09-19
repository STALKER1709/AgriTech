<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\FarmerProfile;
use App\Models\Privilege;
use App\Models\Setting;
use App\Models\SubOrder;
use App\Models\User;
use App\Services\Admin\AuditLogger;
use App\Services\Admin\FarmerValidationService;
use App\Services\Admin\PrivilegeService;
use App\Services\Admin\SettingService;
use App\Services\Admin\UserModerationService;
use Database\Seeders\PrivilegeSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Support\Facades\Notification;

/**
 * Business rule RG11: every sensitive administrative action is recorded with
 * who, what, when, and the before and after.
 */
beforeEach(function () {
    Notification::fake();
    $this->seed(PrivilegeSeeder::class);
    $this->admin = User::factory()->admin()->create();
});

function lastEntry(): AuditLog
{
    return AuditLog::query()->latest('id')->firstOrFail();
}

it('records an approval with what changed', function () {
    $farmer = User::factory()->awaitingValidation()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    app(FarmerValidationService::class)->approve($farmer, $this->admin);

    $entry = lastEntry();

    expect($entry->action)->toBe(AuditLogger::FARMER_APPROVED);
    expect($entry->actor_id)->toBe($this->admin->id);
    expect($entry->auditable_id)->toBe($farmer->id);
    expect($entry->before['status'])->toBe('pending_validation');
    expect($entry->after['status'])->toBe('active');
    expect($entry->after['validated_at'])->not->toBeNull();
    expect($entry->created_at)->not->toBeNull();
});

it('records a rejection together with its reason', function () {
    $farmer = User::factory()->awaitingValidation()->create();
    FarmerProfile::factory()->create(['user_id' => $farmer->id]);

    app(FarmerValidationService::class)->reject($farmer, $this->admin, 'Justificatifs illisibles.');

    $entry = lastEntry();

    expect($entry->action)->toBe(AuditLogger::FARMER_REJECTED);
    expect($entry->after['rejection_reason'])->toBe('Justificatifs illisibles.');
    expect($entry->after['status'])->toBe('rejected');
});

it('records a suspension and a reinstatement', function () {
    $target = User::factory()->client()->create();

    app(UserModerationService::class)->suspend($target, $this->admin);

    expect(lastEntry()->action)->toBe(AuditLogger::USER_SUSPENDED);
    expect(lastEntry()->before['status'])->toBe('active');
    expect(lastEntry()->after['status'])->toBe('suspended');

    app(UserModerationService::class)->reinstate($target, $this->admin);

    expect(lastEntry()->action)->toBe(AuditLogger::USER_REINSTATED);
    expect(lastEntry()->after['status'])->toBe('active');
});

it('records a deletion without copying the data it just removed', function () {
    $target = User::factory()->client()->create(['email' => 'secret@agritech.local']);

    app(UserModerationService::class)->delete($target, $this->admin);

    $entry = lastEntry();

    expect($entry->action)->toBe(AuditLogger::USER_DELETED);
    expect($entry->before['had_email'])->toBeTrue();
    expect($entry->after['had_email'])->toBeFalse();

    // Writing the address into the log would undo the anonymisation it is
    // recording.
    expect(json_encode($entry->before))->not->toContain('secret@agritech.local');
    expect(json_encode($entry->after))->not->toContain('secret@agritech.local');
});

it('records a privilege change as two lists', function () {
    $target = User::factory()->admin()->create();
    $target->privileges()->attach(Privilege::query()->where('code', Privilege::SUSPEND_USERS)->value('id'));

    app(PrivilegeService::class)->sync($target, [Privilege::APPROVE_FARMERS], $this->admin);

    $entry = lastEntry();

    expect($entry->action)->toBe(AuditLogger::PRIVILEGES_UPDATED);
    expect($entry->before['privileges'])->toBe([Privilege::SUSPEND_USERS]);
    expect($entry->after['privileges'])->toBe([Privilege::APPROVE_FARMERS]);
});

it('records nothing when a privilege change changes nothing', function () {
    $target = User::factory()->admin()->create();
    $target->privileges()->attach(Privilege::query()->where('code', Privilege::SUSPEND_USERS)->value('id'));

    app(PrivilegeService::class)->sync($target, [Privilege::SUSPEND_USERS], $this->admin);

    expect(AuditLog::query()->count())->toBe(0);
});

it('records a setting change with both values', function () {
    $this->seed(SettingSeeder::class);
    $setting = Setting::query()->where('key', Setting::PLATFORM_COMMISSION_RATE)->firstOrFail();

    app(SettingService::class)->update($setting, '8', $this->admin);

    $entry = lastEntry();

    expect($entry->action)->toBe(AuditLogger::SETTING_UPDATED);
    expect($entry->before['value'])->toBe('5');
    expect($entry->after['value'])->toBe('8');
    expect($setting->refresh()->integerValue())->toBe(8);
});

it('records nothing when a setting is saved unchanged', function () {
    $this->seed(SettingSeeder::class);
    $setting = Setting::query()->where('key', Setting::PLATFORM_COMMISSION_RATE)->firstOrFail();

    app(SettingService::class)->update($setting, '5', $this->admin);

    expect(AuditLog::query()->count())->toBe(0);
});

it('normalises a boolean setting whatever form it arrives in', function (string $input, string $stored) {
    $this->seed(SettingSeeder::class);
    $setting = Setting::query()->where('key', Setting::PRIOR_MODERATION_ENABLED)->firstOrFail();
    $setting->forceFill(['value' => $stored === '1' ? '0' : '1'])->save();

    app(SettingService::class)->update($setting, $input, $this->admin);

    expect($setting->refresh()->value)->toBe($stored);
})->with([
    'checkbox on' => ['on', '1'],
    'literal true' => ['true', '1'],
    'one' => ['1', '1'],
    'zero' => ['0', '0'],
    'literal false' => ['false', '0'],
]);

it('refuses a non-numeric value for a numeric setting', function () {
    $this->seed(SettingSeeder::class);
    $setting = Setting::query()->where('key', Setting::FARMER_REGISTRATION_FEE)->firstOrFail();

    app(SettingService::class)->update($setting, 'beaucoup', $this->admin);
})->throws(InvalidArgumentException::class);

it('never lets a setting change rewrite a past commission', function () {
    $this->seed(SettingSeeder::class);

    $subOrder = SubOrder::factory()->create([
        'commission_rate_snapshot' => 5,
        'commission_amount' => 500,
        'subtotal_amount' => 10_000,
    ]);

    $setting = Setting::query()->where('key', Setting::PLATFORM_COMMISSION_RATE)->firstOrFail();
    app(SettingService::class)->update($setting, '20', $this->admin);

    expect($subOrder->refresh()->commission_rate_snapshot)->toBe(5);
    expect($subOrder->commission_amount->amount)->toBe(500);
});
