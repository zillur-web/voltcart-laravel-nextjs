<?php

namespace App\Support;

use Illuminate\Validation\Rules\Password;

class PasswordRule
{
    public static function rule(): Password
    {
        return Password::min(8)->mixedCase()->numbers()->symbols();
    }
}
