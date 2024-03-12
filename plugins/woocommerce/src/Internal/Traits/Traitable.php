<?php

namespace Automattic\WooCommerce\Internal\Traits;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use BadMethodCallException;

trait Traitable
{
    /**
     * Methods that have been added.
     *
     * @var array
     */
    protected static array $methods = [];

    /**
     * Add a method to the target class.
     */
    public static function add_method( string $name, callable $method ): void {
        static::$methods[ $name ] = $method;
    }

    /**
     * Add all methods from a trait mixin to the target class.
     */
    public static function add_trait( object $trait ): void {
        $methods = ( new ReflectionClass( $trait ) )->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );

        foreach ( $methods as $method ) {
            $method->setAccessible( true );

            static::add_method( $method->name, $method->invoke( $trait ) );
        }
    }

    /**
     * Check if a method has been added by a given name.
     */
    public static function has_method( string $name ): bool {
        return isset( static::$methods[ $name ] );
    }

    /**
     * Handle static methods.
     */
    public static function __callStatic( $name, $parameters ) {
        if ( ! static::has_method( $name ) ) {
            throw new BadMethodCallException( "Method {$name} does not exist." );
        }

        $method = static::$methods[$name];

        if ( $method instanceof Closure ) {
            return call_user_func_array( Closure::bind( $method, null, static::class ), $parameters );
        }

        return call_user_func_array($method, $parameters);
    }

    /**
     * Magically call the injected methods on the target class.
     */
    public function __call( $name, $parameters ) {
        if (! static::has_method( $name )) {
            throw new BadMethodCallException( "Method {$name} does not exist." );
        }

        $method = static::$methods[ $name ];

        if ( $method instanceof Closure ) {
            return call_user_func_array( $method->bindTo( $this, static::class ), $parameters );
        }

        return call_user_func_array( $method, $parameters );
    }
}