<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Enums\SettingType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Updates a platform parameter, with the before and after RG11 asks for.
 */
final class SettingService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function update(Setting $setting, string $value, User $admin): void
    {
        $value = $this->normalise($setting, $value);

        DB::transaction(function () use ($setting, $value, $admin): void {
            $before = $setting->value;

            if ($before === $value) {
                return;
            }

            $setting->forceFill(['value' => $value])->save();

            $this->audit->record(
                action: AuditLogger::SETTING_UPDATED,
                auditable: $setting,
                before: ['key' => $setting->key, 'value' => $before],
                after: ['key' => $setting->key, 'value' => $value],
                actor: $admin,
            );
        });
    }

    /**
     * Store every type as the canonical string its accessor expects.
     *
     * A boolean arriving as "on", "true" or "1" has to come back as a boolean
     * whichever form was posted, so the shape is settled here rather than
     * guessed at each read.
     */
    private function normalise(Setting $setting, string $value): string
    {
        return match ($setting->type) {
            SettingType::Integer => (string) $this->requireNonNegativeInteger($value),
            SettingType::Boolean => filter_var($value, FILTER_VALIDATE_BOOL) ? '1' : '0',
            SettingType::String => trim($value),
        };
    }

    private function requireNonNegativeInteger(string $value): int
    {
        if (preg_match('/^\d+$/', trim($value)) !== 1) {
            throw new InvalidArgumentException('Ce paramètre attend un nombre entier positif.');
        }

        return (int) trim($value);
    }
}
