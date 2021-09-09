<?php

namespace Unleash\Client\Configuration;

use Unleash\Client\Enum\ContextField;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Exception\InvalidValueException;

final class UnleashContext implements Context
{
    /**
     * @var string|null
     */
    private $currentUserId;
    /**
     * @var string|null
     */
    private $ipAddress;
    /**
     * @var string|null
     */
    private $environment;
    /**
     * @var string|null
     */
    private $sessionId;
    /**
     * @var array<string, string>
     */
    private $customContext = [];
    /**
     * @param array<string,string> $customContext
     */
    public function __construct(?string $currentUserId = null, ?string $environment = null, ?string $ipAddress = null, ?string $sessionId = null, array $customContext = [], ?string $hostname = null)
    {
        $this->currentUserId = $currentUserId;
        $this->environment = $environment;
        $this->ipAddress = $ipAddress;
        $this->sessionId = $sessionId;
        $this->customContext = $customContext;
        $this->setHostname($hostname);
    }
    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    public function getIpAddress(): ?string
    {
        return $this->ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId ?? (session_id() ?: null);
    }

    public function getCustomProperty(string $name): string
    {
        if (!array_key_exists($name, $this->customContext)) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        return $this->customContext[$name];
    }

    public function setCustomProperty(string $name, string $value): \Unleash\Client\Configuration\Context
    {
        $this->customContext[$name] = $value;

        return $this;
    }

    public function hasCustomProperty(string $name): bool
    {
        return array_key_exists($name, $this->customContext);
    }

    public function removeCustomProperty(string $name, bool $silent = true): \Unleash\Client\Configuration\Context
    {
        if (!$this->hasCustomProperty($name) && !$silent) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        unset($this->customContext[$name]);

        return $this;
    }

    public function setCurrentUserId(?string $currentUserId): \Unleash\Client\Configuration\Context
    {
        $this->currentUserId = $currentUserId;

        return $this;
    }

    public function setIpAddress(?string $ipAddress): \Unleash\Client\Configuration\Context
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function setSessionId(?string $sessionId): \Unleash\Client\Configuration\Context
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    public function setEnvironment(?string $environment): \Unleash\Client\Configuration\Context
    {
        $this->environment = $environment;

        return $this;
    }

    public function getHostname(): ?string
    {
        return $this->findContextValue('hostname') ?? (gethostname() ?: null);
    }

    public function setHostname(?string $hostname): self
    {
        if ($hostname === null) {
            $this->removeCustomProperty('hostname');
        } else {
            $this->setCustomProperty('hostname', $hostname);
        }

        return $this;
    }

    /**
     * @param array<string> $values
     */
    public function hasMatchingFieldValue(string $fieldName, array $values): bool
    {
        $fieldValue = $this->findContextValue($fieldName);
        if ($fieldValue === null) {
            return false;
        }

        return in_array($fieldValue, $values, true);
    }

    public function findContextValue(string $fieldName): ?string
    {
        switch ($fieldName) {
            case ContextField::USER_ID:
            case Stickiness::USER_ID:
                return $this->getCurrentUserId();
            case ContextField::SESSION_ID:
            case Stickiness::SESSION_ID:
                return $this->getSessionId();
            case ContextField::IP_ADDRESS:
                return $this->getIpAddress();
            case ContextField::ENVIRONMENT:
                return $this->getEnvironment();
            default:
                return $this->customContext[$fieldName] ?? null;
        }
    }
}
