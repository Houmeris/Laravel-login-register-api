<?php

namespace App\Repositories;

use App\Models\User;

class UserRepository
{
    protected $userModel;

    public function __construct(User $userModel)
    {
        $this->userModel = $userModel;
    }

    public function findByEmail(string $email)
    {
        return $this->userModel->where('email', $email)->first();
    }

    public function findByPhone(string $phone)
    {
        return $this->userModel->where('phone', $phone)->first();
    }

    public function incrementLoginAttempts(User $user)
    {
        $user->increment('login_attempts');
        $user->login_attempted_at = now();
        $user->save();
    }

    public function resetLoginAttempts(User $user)
    {
        $user->login_attempts = 0;
        $user->block_time_extend = 0;
        $user->login_attempted_at = null;
        $user->blocked_until = null;
        $user->save();
    }
}

?>