<?php declare(strict_types=1);

namespace League\Container\Inflector;

use IteratorAggregate;
use League\Container\ContainerAwareInterface;

interface InflectorAggregateInterface extends ContainerAwareInterface, IteratorAggregate
{
    /**
     * Add an inflector to the aggregate.
     *
     * @param string   $type
     * @param callable $callback
     *
     * @return Inflector
     */
    public function add(string $type, callable $callback = null) : Inflector;

    /**
     * Applies all inflectors to an object.
     *
     * @param  object $object
     * @return object
     */
    public function inflect($object);
}
