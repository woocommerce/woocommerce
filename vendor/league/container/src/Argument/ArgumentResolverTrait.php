<?php declare(strict_types=1);

namespace League\Container\Argument;

use League\Container\Container;
use League\Container\Exception\{ContainerException, NotFoundException};
use League\Container\ReflectionContainer;
use Psr\Container\ContainerInterface;
use ReflectionFunctionAbstract;
use ReflectionParameter;

trait ArgumentResolverTrait
{
    /**
     * {@inheritdoc}
     */
    public function resolveArguments(array $arguments) : array
    {
        foreach ($arguments as &$arg) {
            if ($arg instanceof RawArgumentInterface) {
                $arg = $arg->getValue();
                continue;
            }

            if ($arg instanceof ClassNameInterface) {
                $arg = $arg->getValue();
            }

            if (! is_string($arg)) {
                 continue;
            }

            $container = null;

            try {
                $container = $this->getLeagueContainer();
            } catch (ContainerException $e) {
                if ($this instanceof ReflectionContainer) {
                    $container = $this;
                }
            }


            if ($container !== null && $container->has($arg)) {
                $arg = $container->get($arg);

                if ($arg instanceof RawArgumentInterface) {
                    $arg = $arg->getValue();
                }

                continue;
            }
        }

        return $arguments;
    }

    /**
     * {@inheritdoc}
     */
    public function reflectArguments(ReflectionFunctionAbstract $method, array $args = []) : array
    {
        $arguments = array_map(function (ReflectionParameter $param) use ($method, $args) {
            $name  = $param->getName();
            $class = $param->getClass();

            if (array_key_exists($name, $args)) {
                return $args[$name];
            }

            if ($class !== null) {
                return $class->getName();
            }

            if ($param->isDefaultValueAvailable()) {
                return $param->getDefaultValue();
            }

            throw new NotFoundException(sprintf(
                'Unable to resolve a value for parameter (%s) in the function/method (%s)',
                $name,
                $method->getName()
            ));
        }, $method->getParameters());

        return $this->resolveArguments($arguments);
    }

    /**
     * @return ContainerInterface
     */
    abstract public function getContainer() : ContainerInterface;

    /**
     * @return Container
     */
    abstract public function getLeagueContainer() : Container;
}
