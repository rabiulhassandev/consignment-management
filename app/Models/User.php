<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Enums\UserType;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'email', 'password', 'phone', 'company_name', 'address', 'type', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'type' => UserType::class,
            'status' => UserStatus::class,
        ];
    }

    public function suppliers(): HasMany
    {
        return $this->hasMany(Supplier::class, 'customer_id');
    }

    public function consignments(): HasMany
    {
        return $this->hasMany(Consignment::class, 'customer_id');
    }

    public function lcBills(): HasMany
    {
        return $this->hasMany(LcBill::class, 'customer_id');
    }

    public function ttAccounts(): HasMany
    {
        return $this->hasMany(TtAccount::class, 'customer_id');
    }

    public function isStaff(): bool
    {
        return $this->type === UserType::Staff;
    }

    public function isCustomer(): bool
    {
        return $this->type === UserType::Customer;
    }

    public function isApproved(): bool
    {
        return $this->status === UserStatus::Approved;
    }

    public function scopeCustomers(Builder $query): Builder
    {
        return $query->where('type', UserType::Customer);
    }

    public function scopeStaff(Builder $query): Builder
    {
        return $query->where('type', UserType::Staff);
    }
}
