<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use App\Mail\HelloEmail;
use Illuminate\Support\Facades\Mail;
use App\Repositories\UserRepository;
use Carbon\Carbon;

class userService
{
    protected $userRepository;
    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }
    public function register(array $userData): User
    {
        // validate user data
        $validateData = validator($userData, [
            'name' => 'required|string|max:191',
            'phone' => 'required|string|min:7|max:7',
            'email' => 'nullable|string|email|max:191|unique:users',
            'password' => 'required|string|min:8',
            'terms_and_privacy_policy_agree' => 'required|boolean',
        ])->validate();

        // create a new user
        $user = new User();
        $user->name = $validateData['name'];
        $user->phone = "+3706" . $validateData['phone'];
        if(isset($validateData['email']))
        {
            $user->email = $validateData['email'];
        }
        $user->password = Hash::make($validateData['password']);
        $user->terms_and_privacy_policy_agree = $validateData['terms_and_privacy_policy_agree'];
        $user->save();

        if(isset($validateData['email']))
        {
            Mail::to($validateData['email'])->later(now()->addSeconds(2), new HelloEmail);
        }

        return $user;
    }
    public function login(array $credentials): ?string
    {
        if(!isset($credentials['email']))
        {
            $user = $this->userRepository->findByPhone( "+3706" . $credentials['phone']);
            if(!$user)
            {
                return null;
            }
            else
            {
                $token = $user->createToken('authToken')->plainTextToken;
                $this->userRepository->resetLoginAttempts($user);

                return $token;
            }
        }
        $user = $this->userRepository->findByEmail($credentials['email']);
        // attempt to authenticate the user
        if(!$user)
        {
            return null;
        }

        if($user->blocked_until != null)
        {
            if (Carbon::now()->lte($user->blocked_until))
            {
                return null;
            }
        }

        if (!Hash::check($credentials['password'], $user->password))
        {
            $this->userRepository->incrementLoginAttempts($user);

            if ($user->login_attempts == 3)
            {
                $user->blocked_until = now()->addMinutes(3);
                $user->save();
            }
            elseif ($user->login_attempts == 6)
            {
                $user->blocked_until = now()->addMinutes(10);
                $user->save();
            }
            elseif ($user->login_attempts > 6 && $user->login_attempts % 3 == 0)
            {
                if($user->block_time_extend == 0)
                {
                    $user->block_time_extend = 2;
                }
                else
                {
                    $user->block_time_extend = $user->block_time_extend * 2;
                }
                $user->blocked_until = now()->addMinutes(10*$user->block_time_extend);
                $user->save();
            }
            return null;
        }

        // generate a new token for the authenticated user
        $token = $user->createToken('authToken')->plainTextToken;
        $this->userRepository->resetLoginAttempts($user);

        return $token;

    }
}

?>