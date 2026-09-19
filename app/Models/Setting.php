<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SettingType;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A platform parameter an administrator can change without a deployment:
 * registration fee, commission rate, cancellation delays, prior moderation.
 *
 * Values are stored as text and hydrated according to their declared type,
 * which keeps the table simple while still returning real integers and
 * booleans to the callers.
 *
 * @property int $id
 * @property string $key
 * @property string $value
 * @property SettingType $type
 * @property string $label
 * @property string|null $description
 */
#[Fillable(['key', 'value', 'type', 'label', 'description'])]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    public const string FARMER_REGISTRATION_FEE = 'farmer_registration_fee';

    public const string PLATFORM_COMMISSION_RATE = 'platform_commission_rate';

    public const string ORDER_CANCEL_AFTER_MINUTES = 'order_cancel_after_minutes';

    public const string PAYMENT_EXPIRATION_MINUTES = 'payment_expiration_minutes';

    public const string PRIOR_MODERATION_ENABLED = 'prior_moderation_enabled';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    /**
     * The stored value, hydrated to the type it was declared with.
     */
    public function typedValue(): int|bool|string
    {
        return match ($this->type) {
            SettingType::Integer => (int) $this->value,
            SettingType::Boolean => filter_var($this->value, FILTER_VALIDATE_BOOL),
            SettingType::String => $this->value,
        };
    }

    public function integerValue(): int
    {
        return (int) $this->value;
    }

    public function booleanValue(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_BOOL);
    }
}
