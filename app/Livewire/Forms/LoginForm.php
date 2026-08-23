<?php

namespace App\Livewire\Forms;

use App\Services\Security\LoginProtectionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(LoginProtectionService $loginProtection): void
    {
        $loginProtection->ensureIpIsNotBlocked(request());

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            $loginProtection->recordFailedLogin(request(), $this->email);

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();

        if ($user !== null) {
            $loginProtection->recordSuccessfulLogin(request(), $user, $this->email);
        }
    }
}
