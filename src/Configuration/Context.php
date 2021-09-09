<?php

namespace Unleash\Client\Configuration;

/**
 * @method string|null getHostname()
 * @method Context     setHostname(string|null $hostname)
 */
interface Context
{
    public function getCurrentUserId(): ?string;

    public function getIpAddress(): ?string;

    public function getEnvironment(): ?string;

    public function getSessionId(): ?string;

    public function getCustomProperty(string $name): string;

    public function setCustomProperty(string $name, string $value): self;

    public function hasCustomProperty(string $name): bool;

    public function removeCustomProperty(string $name, bool $silent = true): self;

    public function setCurrentUserId(?string $currentUserId): self;

    public function setIpAddress(?string $ipAddress): self;

    public function setEnvironment(?string $environment): self;

    public function setSessionId(?string $sessionId): self;

    /**
     * @param array<string> $values
     */
    public function hasMatchingFieldValue(string $fieldName, array $values): bool;

    public function findContextValue(string $fieldName): ?string;
}
