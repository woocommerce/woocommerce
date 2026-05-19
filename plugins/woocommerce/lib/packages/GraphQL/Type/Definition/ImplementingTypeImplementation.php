<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Type\Definition;

use Automattic\WooCommerce\Vendor\GraphQL\Error\InvariantViolation;
use Automattic\WooCommerce\Vendor\GraphQL\Type\Schema;

/**
 * @see ImplementingType
 */
trait ImplementingTypeImplementation
{
    /**
     * Lazily initialized.
     *
     * @var array<int, InterfaceType>
     */
    private array $interfaces;

    public function implementsInterface(InterfaceType $interfaceType): bool
    {
        if (! isset($this->interfaces)) {
            $this->initializeInterfaces();
        }

        foreach ($this->interfaces as $interface) {
            if ($interfaceType->name === $interface->name) {
                return true;
            }
        }

        return false;
    }

    /** @return array<int, InterfaceType> */
    public function getInterfaces(): array
    {
        if (! isset($this->interfaces)) {
            $this->initializeInterfaces();
        }

        return $this->interfaces;
    }

    private function initializeInterfaces(): void
    {
        $this->interfaces = [];

        if (! isset($this->config['interfaces'])) {
            return;
        }

        $interfaces = $this->config['interfaces'];
        if (is_callable($interfaces)) {
            $interfaces = $interfaces();
        }

        foreach ($interfaces as $interface) {
            $this->interfaces[] = Schema::resolveType($interface); // @phpstan-ignore argument.templateType
        }
    }

    /** @throws InvariantViolation */
    protected function assertValidInterfaces(): void
    {
        if (! isset($this->config['interfaces'])) {
            return;
        }

        $interfaces = $this->config['interfaces'];
        if (is_callable($interfaces)) {
            $interfaces = $interfaces();
        }

        // @phpstan-ignore-next-line should not happen if used correctly
        if (! is_iterable($interfaces)) {
            throw new InvariantViolation("{$this->name} interfaces must be an iterable or a callable which returns an iterable.");
        }
    }
}
