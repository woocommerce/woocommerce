<?php
/**
 * Terminal output.
 *
 * Repo-agnostic.
 */

namespace LocalCi;

/**
 * Everything this tool prints, in one place.
 *
 * Colour carries meaning rather than decoration: green held, red did not and the
 * run stops, yellow is worth noticing but is not a failure. Anything that just
 * supports the line above it stays plain.
 */
final class Output {

	private const COLOURS = array(
		'bold'   => '1',
		'red'    => '31',
		'green'  => '32',
		'yellow' => '33',
	);

	/**
	 * Cached answer for whether colour should be emitted at all.
	 *
	 * @var bool|null
	 */
	private static $colour_available = null;

	/**
	 * A step heading, preceded by a blank line.
	 *
	 * @param string $text Heading text.
	 */
	public static function heading( string $text ): void {
		printf( "\n%s\n", self::paint( $text, 'bold' ) );
	}

	/**
	 * Something held.
	 *
	 * @param string $text Message.
	 */
	public static function pass( string $text ): void {
		printf( "  %s\n", self::paint( '✓ ' . $text, 'green' ) );
	}

	/**
	 * Something did not hold.
	 *
	 * @param string $text Message.
	 */
	public static function fail( string $text ): void {
		printf( "  %s\n", self::paint( '✗ ' . $text, 'red' ) );
	}

	/**
	 * Worth noticing, but not a failure: guidance after a refusal, or a caveat.
	 *
	 * @param string $text   Message.
	 * @param int    $indent Indent level, two spaces each.
	 */
	public static function warn( string $text, int $indent = 2 ): void {
		printf( "%s%s\n", str_repeat( '  ', $indent ), self::paint( $text, 'yellow' ) );
	}

	/**
	 * A plain supporting line.
	 *
	 * @param string $text   Message.
	 * @param int    $indent Indent level, two spaces each.
	 */
	public static function detail( string $text, int $indent = 1 ): void {
		printf( "%s%s\n", str_repeat( '  ', $indent ), $text );
	}

	/**
	 * Print a usage summary.
	 *
	 * @param string $usage Pre-formatted usage text.
	 */
	public static function usage( string $usage ): void {
		printf( "%s\n", $usage );
	}

	/**
	 * Shorten a SHA for reading.
	 *
	 * @param string $sha Full commit SHA.
	 */
	public static function short_sha( string $sha ): string {
		return substr( $sha, 0, 11 ) . '…';
	}

	/**
	 * Wrap text in an ANSI colour, or return it untouched when colour is off.
	 *
	 * @param string $text   Text to colour.
	 * @param string $colour One of bold, red, green, yellow.
	 */
	public static function paint( string $text, string $colour ): string {
		if ( ! self::colour_is_available() || ! isset( self::COLOURS[ $colour ] ) ) {
			return $text;
		}

		return sprintf( "\033[%sm%s\033[0m", self::COLOURS[ $colour ], $text );
	}

	/**
	 * Whether to emit colour at all.
	 *
	 * Off when the output is not a terminal, so piping a run into a file or
	 * pasting it into a ticket gives clean text rather than escape codes, and off
	 * when NO_COLOR is set (https://no-color.org).
	 */
	private static function colour_is_available(): bool {
		if ( null === self::$colour_available ) {
			self::$colour_available = false === getenv( 'NO_COLOR' ) && stream_isatty( STDOUT );
		}

		return self::$colour_available;
	}
}
