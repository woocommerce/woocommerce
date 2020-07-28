<?php declare(strict_types=1);

namespace League\Container\Inflector;

use League\Container\Argument\ArgumentResolverInterface;
use League\Container\Argument\ArgumentResolverTrait;
use League\Container\ContainerAwareTrait;

class Inflector implements ArgumentResolverInterface, InflectorInterface
{
    use ArgumentResolverTrait;
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $type;

    /**
     * @var callable|null
     */
    protected $callback;

    /**
     * @var array
     */
    protected $methods = [];

    /**
     * @var array
     */
    protected $properties = [];

    /**
     * Construct.
     *
     * @param string        $type
     * @param callable|null $callback
     */
    public function __construct(string $type, callable $callback = null)
    {
        $this->type     = $type;
        $this->callback = $callback;
    }

    /**
     * {@inheritdoc}
     */
    public function getType() : string
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     */
    public function invokeMethod(string $name, array $args) : InflectorInterface
    {
        $this->methods[$name] = $args;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function invokeMethods(array $methods) : InflectorInterface
    {
        foreach ($methods as $name => $args) {
            $this->invokeMethod($name, $args);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperty(string $property, $value) : InflectorInterface
    {
        $this->properties[$property] = $this->resolveArguments([$value])[0];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setProperties(array $properties) : InflectorInterface
    {
        foreach ($properties as $property => $value) {
            $this->setProperty($property, $value);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function inflect($object)
    {
        $properties = $this->resolveArguments(array_values($this->properties));
        $properties = array_combine(array_keys($this->properties), $properties);

        // array_combine() can technically return false
        foreach ($properties ?: [] as $property => $value) {
            $object->{$property} = $value;
        }

        foreach ($this->methods as $method => $args) {
            $args = $this->resolveArguments($args);

            /** @var callable $callable */
            $callable = [$object, $method];
            call_user_func_array($callable, $args);
        }

        if ($this->callback !== null) {
            call_user_func($this->callback, $object);
        }
    }
}
