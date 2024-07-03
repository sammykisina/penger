<?php

namespace App\Enums\Auth;

enum OptTypes: string
{
    case VERIFICATION = 'verification';
    case PASSWORD_RESET = 'password-reset';
}
