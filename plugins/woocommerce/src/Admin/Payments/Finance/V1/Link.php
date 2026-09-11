<?php
/**
 * Link value object.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Admin\Payments\Finance\V1;

defined( 'ABSPATH' ) || exit;

/**
 * A titled web link, for example to a provider's payout page (finance data-model version 1).
 *
 * @since 11.2.0
 */
final class Link {

	/**
	 * The link title.
	 *
	 * @var string
	 */
	private string $title;

	/**
	 * The sanitized link URL.
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * Constructor.
	 *
	 * @param string $title The link title.
	 * @param string $url   The link URL. Must be an http or https URL.
	 * @throws \InvalidArgumentException When the title is empty or the URL is not a valid http(s) URL.
	 *
	 * @since 11.2.0
	 */
	public function __construct( string $title, string $url ) {
		$title = trim( $title );
		if ( '' === $title ) {
			throw new \InvalidArgumentException( 'The link title must not be empty.' );
		}

		$sanitized_url = esc_url_raw( $url, array( 'http', 'https' ) );
		if ( '' === $sanitized_url ) {
			throw new \InvalidArgumentException( 'The link URL must be a valid http or https URL.' );
		}

		$this->title = $title;
		$this->url   = $sanitized_url;
	}

	/**
	 * Get the link title.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_title(): string {
		return $this->title;
	}

	/**
	 * Get the sanitized link URL.
	 *
	 * @return string
	 *
	 * @since 11.2.0
	 */
	public function get_url(): string {
		return $this->url;
	}
}
