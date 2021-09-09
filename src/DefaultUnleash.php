<?php

namespace Unleash\Client;

use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Variant\VariantHandler;

final class DefaultUnleash implements Unleash
{
    /**
     * @var \Unleash\Client\Strategy\StrategyHandler[]
     */
    private $strategyHandlers;
    /**
     * @var \Unleash\Client\Repository\UnleashRepository
     */
    private $repository;
    /**
     * @var \Unleash\Client\Client\RegistrationService
     */
    private $registrationService;
    /**
     * @var \Unleash\Client\Configuration\UnleashConfiguration
     */
    private $configuration;
    /**
     * @var \Unleash\Client\Metrics\MetricsHandler
     */
    private $metricsHandler;
    /**
     * @var \Unleash\Client\Variant\VariantHandler
     */
    private $variantHandler;
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function __construct(iterable $strategyHandlers, UnleashRepository $repository, RegistrationService $registrationService, UnleashConfiguration $configuration, MetricsHandler $metricsHandler, VariantHandler $variantHandler)
    {
        $this->strategyHandlers = $strategyHandlers;
        $this->repository = $repository;
        $this->registrationService = $registrationService;
        $this->configuration = $configuration;
        $this->metricsHandler = $metricsHandler;
        $this->variantHandler = $variantHandler;
        if ($configuration->isAutoRegistrationEnabled()) {
            $this->register();
        }
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $defaultContext = $this->configuration->getContextProvider()->getContext();
        $context = $context ? self::mergeContext($defaultContext, $context) : $defaultContext;

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null) {
            return $default;
        }

        if (!$feature->isEnabled()) {
            $this->metricsHandler->handleMetrics($feature, false);

            return false;
        }

        $strategies = $feature->getStrategies();
        if (!is_countable($strategies)) {
            $strategies = iterator_to_array($strategies);
        }
        if (!count($strategies)) {
            $this->metricsHandler->handleMetrics($feature, true);

            return true;
        }

        foreach ($strategies as $strategy) {
            $handlers = $this->findStrategyHandlers($strategy);
            if (!count($handlers)) {
                continue;
            }
            foreach ($handlers as $handler) {
                if ($handler->isEnabled($strategy, $context)) {
                    $this->metricsHandler->handleMetrics($feature, true);

                    return true;
                }
            }
        }

        $this->metricsHandler->handleMetrics($feature, false);

        return false;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant
    {
        $fallbackVariant = $fallbackVariant ?? $this->variantHandler->getDefaultVariant();
        $context = $context ?? $this->configuration->getContextProvider()->getContext();

        $feature = $this->repository->findFeature($featureName);
        if ($feature === null || !$feature->isEnabled() || !count($feature->getVariants())) {
            return $fallbackVariant;
        }

        $variant = $this->variantHandler->selectVariant($feature, $context);
        if ($variant !== null) {
            $this->metricsHandler->handleMetrics($feature, true, $variant);
        }

        return $variant  ?? $fallbackVariant;
    }

    public function register(): bool
    {
        return $this->registrationService->register($this->strategyHandlers);
    }

    /**
     * @return array<StrategyHandler>
     */
    private function findStrategyHandlers(Strategy $strategy): array
    {
        $handlers = [];
        foreach ($this->strategyHandlers as $strategyHandler) {
            if ($strategyHandler->supports($strategy)) {
                $handlers[] = $strategyHandler;
            }
        }

        return $handlers;
    }

    private static function mergeContext(Context $context1, Context $context2): Context
    {
        $class = new \ReflectionClass(UnleashContext::class);
        $classProperties = $class->getProperties();

        foreach ($classProperties as $classProperty) {
            $classProperty->setAccessible(true);
            $value = $classProperty->getValue($context2);
            if ($value) {
                $classProperty->setValue($context1, $classProperty->getValue($context2));
            }
        }

        return $context1;
    }
}
