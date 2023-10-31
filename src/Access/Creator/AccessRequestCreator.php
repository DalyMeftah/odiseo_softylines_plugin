<?php

declare(strict_types=1);

namespace Odiseo\SyliusRbacPlugin\Access\Creator;

use Odiseo\SyliusRbacPlugin\Access\Exception\UnresolvedRouteNameException;
use Odiseo\SyliusRbacPlugin\Access\Model\AccessRequest;
use Odiseo\SyliusRbacPlugin\Access\Model\OperationType;
use Odiseo\SyliusRbacPlugin\Access\Model\Section;

final class AccessRequestCreator implements AccessRequestCreatorInterface
{
    /** @var array */
    private $configuration;

    public function __construct(array $configuration)
    {
        $this->configuration = $configuration;
    }

    public function createFromRouteName(string $routeName, string $requestMethod): AccessRequest
    {
        $operationType = $this->resolveOperationType($requestMethod);

        foreach ($this->configuration['configuration'] as $configurationRoutePrefix) {
            if (strpos($routeName, $configurationRoutePrefix) === 0) {
                return new AccessRequest(Section::configuration(), $operationType);
            }
        }

        foreach ($this->configuration['customers_management'] as $customersRoutePrefix) {
            if (strpos($routeName, $customersRoutePrefix) === 0) {
                return new AccessRequest(Section::customers(), $operationType);
            }
        }

        foreach ($this->configuration['marketing_management'] as $marketingRoutePrefix) {
            if (strpos($routeName, $marketingRoutePrefix) === 0) {
                return new AccessRequest(Section::marketing(), $operationType);
            }
        }

        foreach ($this->configuration['sales_management'] as $salesRoutePrefix) {
            if (strpos($routeName, $salesRoutePrefix) === 0) {
                return new AccessRequest(Section::sales(), $operationType);
            }
        }

        foreach ($this->configuration['catalog_management'] as $catalogRoutePrefix) {
            if (strpos($routeName, $catalogRoutePrefix) === 0) {
                return new AccessRequest(Section::catalog(), $operationType);
            }
        }

        foreach ($this->configuration['custom'] as $sectionName => $sectionPrefixes) {
            foreach ($sectionPrefixes as $prefix) {
                if (strpos($routeName, $prefix) === 0) {
                    return new AccessRequest(Section::ofType($sectionName), $operationType);
                }
            }
        }

        throw UnresolvedRouteNameException::withRouteName($routeName);
    }

    public function resolveOperationType(string $requestMethod): OperationType
    {
        if ('GET' === $requestMethod || 'HEAD' === $requestMethod) {
            return OperationType::read();
        }

        return OperationType::write();
    }
}
