<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Only the user who created a record (its user_id) may change or delete
 * it. The admin can moderate anything.
 */
abstract class OwnedByUserPolicy
{
    public function before(User $user): ?bool
    {
        return $user->email === config('admin.email') ? true : null;
    }

    public function update(User $user, Model $model): bool
    {
        return (string) $user->id === (string) $model->user_id;
    }

    public function delete(User $user, Model $model): bool
    {
        return (string) $user->id === (string) $model->user_id;
    }
}
