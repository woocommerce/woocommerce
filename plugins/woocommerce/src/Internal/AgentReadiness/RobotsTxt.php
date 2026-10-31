<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\AgentReadiness;

defined( 'ABSPATH' ) || exit;

/**
 * Reads a robots.txt as RFC 9309 does: a crawler follows the group that names
 * it, or the `*` group when none does; the longest matching rule wins, and
 * Allow wins a tie. `*` matches any characters and a final `$` ends the path.
 *
 * @since 11.4.0
 */
class RobotsTxt {

	/**
	 * Rules by lowercase user agent, each a pair of allow and path pattern.
	 *
	 * @var array<string, array<int, array{0:bool,1:string}>>
	 */
	private array $rules = array();

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
		$agents   = array();
		$in_rules = false;
		$lines    = preg_split( '/\r\n|\r|\n/', $text );
		foreach ( false === $lines ? array() : $lines as $line ) {
			$parts = explode( ':', (string) preg_replace( '/#.*/', '', $line ), 2 );
			if ( 2 !== count( $parts ) ) {
				continue;
			}
			$key   = strtolower( trim( $parts[0] ) );
			$value = trim( $parts[1] );
			if ( 'user-agent' === $key ) {
				if ( $in_rules ) {
					$agents   = array();
					$in_rules = false;
				}
				$agents[] = strtolower( $value );
				// A crawler named in its own group follows that group, even one with no rules.
				$this->rules[ strtolower( $value ) ] = $this->rules[ strtolower( $value ) ] ?? array();
			} elseif ( 'allow' === $key || 'disallow' === $key ) {
				$in_rules = true;
				foreach ( $agents as $agent ) {
					$this->rules[ $agent ][] = array( 'allow' === $key, $value );
				}
			} elseif ( 'sitemap' === $key && '' !== $value ) {
				$this->sitemaps[] = $value;
			}
		}
	}

	/**
	 * Whether a crawler may load a path.
	 *
	 * @param string $agent Crawler product token, such as OAI-SearchBot.
	 * @param string $path  Path from the host root, such as /llms.txt.
	 */
	public function allows( string $agent, string $path ): bool {
		$rules    = $this->rules[ strtolower( $agent ) ] ?? $this->rules['*'] ?? array();
		$allowed  = true;
		$best_len = -1;
		foreach ( $rules as list( $allow, $pattern ) ) {
			// An empty Disallow allows everything, so it never decides.
			if ( '' === $pattern || ! self::matches( $pattern, $path ) ) {
				continue;
			}
			$len = strlen( $pattern );
			if ( $len > $best_len || ( $len === $best_len && $allow ) ) {
				$allowed  = $allow;
				$best_len = $len;
			}
		}
		return $allowed;
	}

	/**
	 * Sitemap URLs listed in the file.
	 *
	 * @return array<int, string>
	 */
	public function sitemaps(): array {
		return $this->sitemaps;
	}

	/**
	 * Whether a rule's path pattern matches a path.
	 *
	 * @param string $pattern Rule path, with * and a final $.
	 * @param string $path    Path.
	 */
	private static function matches( string $pattern, string $path ): bool {
		// Runs of * match the same as one, and would only add backtracking.
		$anchored = str_ends_with( $pattern, '$' );
		$pattern  = (string) preg_replace( '/\*+/', '*', $anchored ? substr( $pattern, 0, -1 ) : $pattern );
		$regex    = '#^' . str_replace( '\*', '.*', preg_quote( $pattern, '#' ) ) . ( $anchored ? '$' : '' ) . '#';
		return 1 === preg_match( $regex, $path );
	}
}
