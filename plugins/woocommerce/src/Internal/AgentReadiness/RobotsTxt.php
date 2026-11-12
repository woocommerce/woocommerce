<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Reads the Sitemap lines of a robots.txt. Its crawler rules aren't judged from inside the store:
 * a host or CDN can rewrite the file, and a request from the store to itself skips them.
 *
 * @since 11.4.0
 */
class RobotsTxt {

	/**
	 * Sitemap URLs the file lists.
	 *
	 * @var array<int, string>
	 */
	private array $sitemaps = array();

	/**
	 * Parse a robots.txt.
	 *
	 * @param string $text File contents.
	 */
	public function __construct( string $text ) {
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		foreach ( false === $lines ? array() : $lines as $line ) {
			$parts = explode( ':', (string) preg_replace( '/#.*/', '', $line ), 2 );
			if ( 2 === count( $parts ) && 'sitemap' === strtolower( trim( $parts[0] ) ) && '' !== trim( $parts[1] ) ) {
				$this->sitemaps[] = trim( $parts[1] );
			}
		}
	}

	/**
	 * Sitemap URLs listed in the file.
	 *
	 * @return array<int, string>
	 */
	public function sitemaps(): array {
		return $this->sitemaps;
	}
}
