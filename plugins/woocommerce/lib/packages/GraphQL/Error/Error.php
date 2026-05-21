<?php declare(strict_types=1);

namespace Automattic\WooCommerce\Vendor\GraphQL\Error;

use Automattic\WooCommerce\Vendor\GraphQL\Language\AST\Node;
use Automattic\WooCommerce\Vendor\GraphQL\Language\Source;
use Automattic\WooCommerce\Vendor\GraphQL\Language\SourceLocation;

/**
 * Describes an Error found during the parse, validate, or
 * execute phases of performing a Automattic\WooCommerce\Vendor\GraphQL operation. In addition to a message
 * and stack trace, it also includes information about the locations in a
 * Automattic\WooCommerce\Vendor\GraphQL document and/or execution result that correspond to the Error.
 *
 * When the error was caused by an exception thrown in resolver, original exception
 * is available via `getPrevious()`.
 *
 * Also read related docs on [error handling](error-handling.md)
 *
 * Class extends standard PHP `\Exception`, so all standard methods of base `\Exception` class
 * are available in addition to those listed below.
 *
 * @see \Automattic\WooCommerce\Vendor\GraphQL\Tests\Error\ErrorTest
 */
class Error extends \Exception implements \JsonSerializable, ClientAware, ProvidesExtensions
{
    /**
     * Lazily initialized.
     *
     * @var array<int, SourceLocation>
     */
    private array $locations;

    /**
     * An array describing the JSON-path into the execution response which
     * corresponds to this error. Only included for errors during execution.
     * When fields are aliased, the path includes aliases.
     *
     * @var list<int|string>|null
     */
    public ?array $path;

    /**
     * An array describing the JSON-path into the execution response which
     * corresponds to this error. Only included for errors during execution.
     * This will never include aliases.
     *
     * @var list<int|string>|null
     */
    public ?array $unaliasedPath;

    /**
     * An array of Automattic\WooCommerce\Vendor\GraphQL AST Nodes corresponding to this error.
     *
     * @var array<Node>|null
     */
    public ?array $nodes;

    /**
     * The source Automattic\WooCommerce\Vendor\GraphQL document for the first location of this error.
     *
     * Note that if this Error represents more than one node, the source may not
     * represent nodes after the first node.
     */
    private ?Source $source;

    /** @var array<int, int>|null */
    private ?array $positions;

    private bool $isClientSafe;

    /** @var array<string, mixed>|null */
    protected ?array $extensions;

    /**
     * @param iterable<array-key, Node|null>|Node|null $nodes
     * @param array<int, int>|null $positions
     * @param list<int|string>|null $path
     * @param array<string, mixed>|null $extensions
     * @param list<int|string>|null $unaliasedPath
     */
    public function __construct(
        string $message = '',
        $nodes = null,
        ?Source $source = null,
        ?array $positions = null,
        ?array $path = null,
        ?\Throwable $previous = null,
        ?array $extensions = null,
        ?array $unaliasedPath = null
    ) {
        parent::__construct($message, 0, $previous);

        // Compute list of blame nodes.
        if ($nodes instanceof \Traversable) {
            /** @phpstan-ignore arrayFilter.strict */
            $this->nodes = array_filter(iterator_to_array($nodes));
        } elseif (is_array($nodes)) {
            $this->nodes = array_filter($nodes);
        } elseif ($nodes !== null) {
            $this->nodes = [$nodes];
        } else {
            $this->nodes = null;
        }

        $this->source = $source;
        $this->positions = $positions;
        $this->path = $path;
        $this->unaliasedPath = $unaliasedPath;

        if (is_array($extensions) && $extensions !== []) {
            $this->extensions = $extensions;
        } elseif ($previous instanceof ProvidesExtensions) {
            $this->extensions = $previous->getExtensions();
        } else {
            $this->extensions = null;
        }

        $this->isClientSafe = $previous instanceof ClientAware
            ? $previous->isClientSafe()
            : $previous === null;
    }

    /**
     * Given an arbitrary Error, presumably thrown while attempting to execute a
     * Automattic\WooCommerce\Vendor\GraphQL operation, produce a new GraphQLError aware of the location in the
     * document responsible for the original Error.
     *
     * @param mixed $error
     * @param iterable<Node>|Node|null $nodes
     * @param list<int|string>|null $path
     * @param list<int|string>|null $unaliasedPath
     */
    public static function createLocatedError($error, $nodes = null, ?array $path = null, ?array $unaliasedPath = null): Error
    {
        if ($error instanceof self) {
            if ($error->isLocated()) {
                return $error;
            }

            $nodes ??= $error->getNodes();
            $path ??= $error->getPath();
            $unaliasedPath ??= $error->getUnaliasedPath();
        }

        $source = null;
        $originalError = null;
        $positions = [];
        $extensions = [];

        if ($error instanceof self) {
            $message = $error->getMessage();
            $originalError = $error;
            $source = $error->getSource();
            $positions = $error->getPositions();
            $extensions = $error->getExtensions();
        } elseif ($error instanceof InvariantViolation) {
            $message = $error->getMessage();
            $originalError = $error->getPrevious() ?? $error;
        } elseif ($error instanceof \Throwable) {
            $message = $error->getMessage();
            $originalError = $error;
        } else {
            $message = (string) $error;
        }

        $nonEmptyMessage = $message === ''
            ? 'An unknown error occurred.'
            : $message;

        return new static(
            $nonEmptyMessage,
            $nodes,
            $source,
            $positions,
            $path,
            $originalError,
            $extensions,
            $unaliasedPath
        );
    }

    protected function isLocated(): bool
    {
        $path = $this->getPath();
        $nodes = $this->getNodes();

        return $path !== null
            && $path !== []
            && $nodes !== null
            && $nodes !== [];
    }

    public function isClientSafe(): bool
    {
        return $this->isClientSafe;
    }

    public function getSource(): ?Source
    {
        return $this->source
            ??= $this->nodes[0]->loc->source
            ?? null;
    }

    /** @return array<int, int> */
    public function getPositions(): array
    {
        if (! isset($this->positions)) {
            $this->positions = [];

            if (isset($this->nodes)) {
                foreach ($this->nodes as $node) {
                    if (isset($node->loc->start)) {
                        $this->positions[] = $node->loc->start;
                    }
                }
            }
        }

        return $this->positions;
    }

    /**
     * An array of locations within the source Automattic\WooCommerce\Vendor\GraphQL document which correspond to this error.
     *
     * Each entry has information about `line` and `column` within source Automattic\WooCommerce\Vendor\GraphQL document:
     * $location->line;
     * $location->column;
     *
     * Errors during validation often contain multiple locations, for example to
     * point out to field mentioned in multiple fragments. Errors during execution include a
     * single location, the field which produced the error.
     *
     * @return array<int, SourceLocation>
     *
     * @api
     */
    public function getLocations(): array
    {
        if (! isset($this->locations)) {
            $positions = $this->getPositions();
            $source = $this->getSource();
            $nodes = $this->getNodes();

            $this->locations = [];
            if ($source !== null && $positions !== []) {
                foreach ($positions as $position) {
                    $this->locations[] = $source->getLocation($position);
                }
            } elseif ($nodes !== null && $nodes !== []) {
                foreach ($nodes as $node) {
                    if (isset($node->loc->source)) {
                        $this->locations[] = $node->loc->source->getLocation($node->loc->start);
                    }
                }
            }
        }

        return $this->locations;
    }

    /** @return array<Node>|null */
    public function getNodes(): ?array
    {
        return $this->nodes;
    }

    /**
     * Returns an array describing the path from the root value to the field which produced this error.
     * Only included for execution errors. When fields are aliased, the path includes aliases.
     *
     * @return list<int|string>|null
     *
     * @api
     */
    public function getPath(): ?array
    {
        return $this->path;
    }

    /**
     * Returns an array describing the path from the root value to the field which produced this error.
     * Only included for execution errors. This will never include aliases.
     *
     * @return list<int|string>|null
     *
     * @api
     */
    public function getUnaliasedPath(): ?array
    {
        return $this->unaliasedPath;
    }

    /** @return array<string, mixed>|null */
    public function getExtensions(): ?array
    {
        return $this->extensions;
    }

    /**
     * Specify data which should be serialized to JSON.
     *
     * @see http://php.net/manual/en/jsonserializable.jsonserialize.php
     *
     * @return array<string, mixed> data which can be serialized by <b>json_encode</b>,
     *                              which is a value of any type other than a resource
     */
    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return FormattedError::createFromException($this);
    }

    public function __toString(): string
    {
        return FormattedError::printError($this);
    }
}
