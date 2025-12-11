<?php

declare(strict_types=1);

namespace WooCommerce\RestApiDocs\Command;

/**
 * Interface for CLI commands.
 */
interface CommandInterface
{
    /**
     * Get the command name.
     */
    public function getName(): string;

    /**
     * Get a short description of the command.
     */
    public function getDescription(): string;

    /**
     * Execute the command.
     *
     * @param array<string, mixed> $options Command options
     * @return int Exit code (0 for success, non-zero for errors)
     */
    public function execute(array $options): int;
}
