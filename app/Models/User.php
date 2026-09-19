<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SubscriptionStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Exceptions\InvalidStatusTransition;
use App\Support\PhoneNumber;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $email
 * @property string|null $phone
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property UserRole $role
 * @property UserStatus $status
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $name
 * @property-read FarmerProfile|null $farmerProfile
 */
#[Fillable(['first_name', 'last_name', 'email', 'phone', 'password', 'role', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    // Email verification is available but not enforced: the specification
    // makes it optional. Implementing MustVerifyEmail here is the single
    // switch that turns it on, and that call belongs to phase 2.

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'status' => UserStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /** @return HasOne<FarmerProfile, $this> */
    public function farmerProfile(): HasOne
    {
        return $this->hasOne(FarmerProfile::class);
    }

    /** @return HasOne<Cart, $this> */
    public function cart(): HasOne
    {
        return $this->hasOne(Cart::class, 'client_id');
    }

    /**
     * How many lines the client has waiting, for the badge in the navigation.
     */
    public function cartItemCount(): int
    {
        return $this->cart?->items()->count() ?? 0;
    }

    /** @return BelongsToMany<Privilege, $this> */
    public function privileges(): BelongsToMany
    {
        return $this->belongsToMany(Privilege::class)->withTimestamps();
    }

    /** @return HasMany<Product, $this> */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'farmer_id');
    }

    /** @return HasMany<Training, $this> */
    public function trainings(): HasMany
    {
        return $this->hasMany(Training::class, 'farmer_id');
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'client_id');
    }

    /** @return HasMany<SubOrder, $this> */
    public function subOrders(): HasMany
    {
        return $this->hasMany(SubOrder::class, 'farmer_id');
    }

    /** @return HasMany<TrainingPurchase, $this> */
    public function trainingPurchases(): HasMany
    {
        return $this->hasMany(TrainingPurchase::class, 'client_id');
    }

    /** @return HasMany<Subscription, $this> */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'client_id');
    }

    /** @return HasMany<Payment, $this> */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /** @return HasMany<Conversation, $this> */
    public function clientConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'client_id');
    }

    /** @return HasMany<Conversation, $this> */
    public function farmerConversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'farmer_id');
    }

    /** @return HasMany<Message, $this> */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id');
    }

    /** @return HasMany<AuditLog, $this> */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'actor_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * The full name, assembled rather than stored.
     *
     * @return Attribute<string, never>
     */
    protected function name(): Attribute
    {
        return Attribute::get(fn (): string => trim($this->first_name.' '.$this->last_name));
    }

    /**
     * Phone numbers are stored in one canonical shape.
     *
     * The unique constraint on this column is only meaningful if every write
     * normalises first: "650 00 00 01" and "+237650000001" are the same
     * number and must collide. Null is allowed through untouched: that is an
     * anonymised account, per business rule RG08.
     *
     * @return Attribute<string|null, string|null>
     */
    protected function phone(): Attribute
    {
        return Attribute::set(function (?string $value): ?string {
            if ($value === null) {
                return null;
            }

            return PhoneNumber::tryParse($value)?->toE164() ?? $value;
        });
    }

    public function initials(): string
    {
        return Str::upper(Str::substr($this->first_name, 0, 1).Str::substr($this->last_name, 0, 1));
    }

    /*
    |--------------------------------------------------------------------------
    | Role and status
    |--------------------------------------------------------------------------
    */

    public function isClient(): bool
    {
        return $this->role === UserRole::Client;
    }

    public function isFarmer(): bool
    {
        return $this->role === UserRole::Farmer;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    /**
     * Business rule RG01: nothing gets published by a farmer whose account is
     * not active yet.
     */
    public function canPublish(): bool
    {
        return $this->isFarmer() && $this->isActive();
    }

    /**
     * Business rule RG05: an active subscription opens the trainings that are
     * marked as included in it.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()
            ->where('status', SubscriptionStatus::Active)
            ->whereNotNull('ends_at')
            ->where('ends_at', '>', now())
            ->exists();
    }

    public function hasPrivilege(string $code): bool
    {
        return $this->isAdmin()
            && $this->privileges->contains(fn (Privilege $privilege): bool => $privilege->code === $code);
    }

    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    |
    | Every status change goes through one of these. Assigning $user->status
    | directly bypasses the domain rules and must not happen outside here.
    |
    */

    public function transitionTo(UserStatus $target): void
    {
        if (! $this->status->canTransitionTo($target)) {
            throw InvalidStatusTransition::between('user account', $this->status, $target);
        }

        $this->status = $target;
        $this->save();
    }

    /**
     * Business rule RG02: only a successful registration fee payment moves a
     * farmer account forward for review.
     */
    public function markAwaitingValidation(): void
    {
        $this->transitionTo(UserStatus::PendingValidation);
    }

    public function approve(User $validatedBy): void
    {
        $this->transitionTo(UserStatus::Active);

        $this->farmerProfile?->markValidatedBy($validatedBy);
    }

    public function reject(User $rejectedBy, string $reason): void
    {
        $this->transitionTo(UserStatus::Rejected);

        $this->farmerProfile?->markRejectedBy($rejectedBy, $reason);
    }

    public function suspend(): void
    {
        $this->transitionTo(UserStatus::Suspended);
    }

    public function reinstate(): void
    {
        $this->transitionTo(UserStatus::Active);
    }

    /**
     * Business rule RG08: a deleted account keeps its row and loses its
     * person.
     *
     * The row survives so that the orders and payments pointing at it stay
     * readable; the personal data does not. Email and phone go to null rather
     * than to a placeholder — anonymising means removing the data, not
     * replacing it with something that still looks like a person.
     */
    public function anonymise(): void
    {
        $this->transitionTo(UserStatus::Deleted);

        $this->forceFill([
            'first_name' => 'Compte',
            'last_name' => 'supprimé',
            'email' => null,
            'phone' => null,
            'email_verified_at' => null,
            'password' => Hash::make(Str::random(64)),
            'remember_token' => null,
        ])->save();

        $this->privileges()->detach();
    }

    public function isAnonymised(): bool
    {
        return $this->status === UserStatus::Deleted;
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** @param  Builder<$this>  $query */
    public function scopeRole(Builder $query, UserRole $role): void
    {
        $query->where('role', $role);
    }

    /** @param  Builder<$this>  $query */
    public function scopeStatus(Builder $query, UserStatus $status): void
    {
        $query->where('status', $status);
    }
}
