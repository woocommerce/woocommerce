<?php
/**
 * This file is part of the MailPoet plugin
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);

use Automattic\WooCommerce\EmailEditor\Container;
use Automattic\WooCommerce\EmailEditor\Email_Editor_Container;

/**
 * Base class for MailPoet tests.
 *
 * @property IntegrationTester $tester
 */
abstract class Email_Editor_Integration_Test_Case extends \WP_UnitTestCase {
	/**
	 * The DI container.
	 *
	 * @var Container
	 */
	public Container $di_container;

	/**
	 * Set up before each test.
	 */
	public function setUp(): void {
		$this->initContainer();
		parent::setUp();
	}

	/**
	 * Check if the HTML is valid.
	 *
	 * @param string $html The HTML to check.
	 */
	protected function checkValidHTML( string $html ): void {
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );

		// Check for errors during parsing.
		$errors = libxml_get_errors();
		libxml_clear_errors();

		$this->assertEmpty( $errors, 'HTML is not valid: ' . $html );
	}

	/**
	 * Get a service from the DI container.
	 *
	 * @template T of object
	 * @param string $id The service ID.
	 * @param array  $overrides The properties to override.
	 * @return T
	 * @phpstan-param class-string<T> $id The service ID.
	 */
	public function getServiceWithOverrides( string $id, array $overrides ): object {
		$instance = $this->di_container->get( $id );

		foreach ( $overrides as $property => $value ) {
			$reflection = new ReflectionClass( $instance );
			if ( $reflection->hasProperty( $property ) ) {
				$prop = $reflection->getProperty( $property );
				$prop->setAccessible( true );
				$prop->setValue( $instance, $value );
			}
		}

		return $instance;
	}

	/**
	 * Initialize the DI container.
	 */
	protected function initContainer(): void {
		$this->di_container = Email_Editor_Container::container();
	}
}
