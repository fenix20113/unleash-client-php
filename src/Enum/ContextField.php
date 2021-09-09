<?php

namespace Unleash\Client\Enum;

final class ContextField
{
    public const USER_ID = 'userId';

    public const ENVIRONMENT = 'environment';

    public const SESSION_ID = 'sessionId';

    public const IP_ADDRESS = 'remoteAddress';

    public const REMOTE_ADDRESS = self::IP_ADDRESS;
}
