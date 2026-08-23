<?php

declare(strict_types=1);

namespace App\Enums;

enum SecurityEventType: string
{
    case LoginFailed = 'LOGIN_FAILED';
    case LoginRateLimited = 'LOGIN_RATE_LIMITED';
    case LoginIpBlocked = 'LOGIN_IP_BLOCKED';
    case LoginIpUnblocked = 'LOGIN_IP_UNBLOCKED';
    case LoginSuccess = 'LOGIN_SUCCESS';
    case MfaChallengeFailed = 'MFA_CHALLENGE_FAILED';
    case MfaEnabled = 'MFA_ENABLED';
    case MfaDisabled = 'MFA_DISABLED';
    case SessionRevoked = 'SESSION_REVOKED';
    case PasswordChanged = 'PASSWORD_CHANGED';
    case UserRoleChanged = 'USER_ROLE_CHANGED';
    case SecuritySettingChanged = 'SECURITY_SETTING_CHANGED';
    case Fail2banBanDetected = 'FAIL2BAN_BAN_DETECTED';
}
