<?php

namespace Automattic\WooCommerce\Tools\DependencyManagement;

/**
 * The original PHP League's `ReflectionContainer`, used for autowiring, provides an all-or-nothing approach
 * for registering singletons: either all the resolved objects are, or none is. This class provides a new `share`
 * method that allows to register classes to be resolved as singleton objects even if `cacheResolutions` is disabled.
 *
 * @package Automattic\WooCommerce\Tools\DependencyManagement
 */
class ReflectionContainer extends \League\Container\ReflectionContainer {

	/**
	 * @var array Names of the classes to always be resolved as singletons.
	 */
	protected $classes_to_always_cache = array();

	/**
	 * Register a class to be always resolved as a singleton object, even if `cacheResolutions` is disabled.
	 *
	 * @param string $class_name The name of the class to register.
	 */
	public function share( string $class_name ) {
		if ( ! in_array( $class_name, $this->classes_to_always_cache, true ) ) {
			array_push( $this->classes_to_always_cache, $class_name );
		}
	}

	/**
	 * {@inheritdoc}
	 *
	 * @throws ReflectionException
	 */
	public function get( $id, array $args = array() ) {
		if ( true === $this->cacheResolutions || ! in_array( $id, $this->classes_to_always_cache ) ) {
			return parent::get( $id, $args );
		}

		if ( array_key_exists( $id, $this->cache ) ) {
			return $this->cache[ $id ];
		}

		$resolution         = parent::get( $id, $args );
		$this->cache[ $id ] = $resolution;

		return $resolution;
	}
}
