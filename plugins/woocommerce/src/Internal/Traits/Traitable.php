<?php

namespace Automattic\WooCommerce\Internal\Traits;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
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
     * Properties that have been added.
     *
     * @var array
     */
    protected static array $properties = [];

    /**
     * Add a method to the target class.
     */
    public static function add_method( string $name, callable $method ): void {
        static::$methods[ $name ] = $method;
    }

    /**
     * Add a property to the target class.
     */
    public static function add_property( string $name, $trait ): void {
        static::$properties[ $name ] = $trait;
    }

    /**
     * Add all methods from a trait mixin to the target class.
     */
    public static function add_trait( object $trait ): void {
        $reflection_class = new ReflectionClass( $trait );
        $methods          = $reflection_class->getMethods(
            ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_PROTECTED
        );
        $properties       = $reflection_class->getProperties(
            ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED
        );

        foreach ( $methods as $method ) {
            $method->setAccessible( true );

            static::add_method( $method->name, $method->invoke( $trait ) );
        }

        foreach ( $properties as $property ) {
            $property->setAccessible( true );

            static::add_property( $property->name, $trait );
        }
    }

    /**
     * Check if a method has been added by a given name.
     */
    public static function has_method( string $name ): bool {
        return isset( static::$methods[ $name ] );
    }

    /**
     * Check if a property has been added by a given name.
     */
    public static function has_property( string $name ): bool {
        return isset( static::$properties[ $name ] );
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
    
    public function __get( $key ) {
        parent::__get( $key );
        if (! static::has_property( $key )) {
            throw new BadMethodCallException( "Property {$key} does not exist." );
        }

        $trait               = static::$properties[ $key ];
        $reflection_class    = new ReflectionClass( $trait );
        $reflection_property = $reflection_class->getProperty( $key );
        $reflection_property->setAccessible(true);
        return $reflection_property->getValue( $trait );
    }
}