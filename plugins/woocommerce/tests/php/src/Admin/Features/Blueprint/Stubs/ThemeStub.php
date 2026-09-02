<?php

declare(strict_types=1);

namespace Automattic\WooCommerce\Tests\Admin\Features\Blueprint\Stubs;

/**
 * Serializable WP_Theme-like test double.
 */
final class ThemeStub {
	/**
	 * Theme stylesheet.
	 *
	 * @var string
	 */
	private $stylesheet;

	/**
	 * Theme name.
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Initialize the theme stub.
	 *
	 * @param string $stylesheet Theme stylesheet.
	 * @param string $name Theme name.
	 */
	public function __construct( string $stylesheet, string $name ) {
		$this->stylesheet = $stylesheet;
		$this->name       = $name;
	}

	/**
	 * Get the theme stylesheet.
	 *
	 * @return string
	 */
	public function get_stylesheet(): string {
		return $this->stylesheet;
	}

	/**
	 * Get a theme header.
	 *
	 * @param string $header Theme header name.
	 * @return string
	 */
	public function get( string $header ): string {
		return 'Name' === $header ? $this->name : '';
	}
}
