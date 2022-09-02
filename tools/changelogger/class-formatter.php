<?php
/**
 * Formatter class
 *
 * @package  WooCommerce
 */

namespace Automattic\WooCommerce\MonorepoTools\Changelogger;

/**
 * Base Jetpack Changelogger Formatter for WooCommerce
 */

use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\KeepAChangelogParser;
use Automattic\Jetpack\Changelogger\PluginTrait;

/**
 * Base Jetpack Changelogger Formatter for WooCommerce
 *
 * Note: Since the "filename" loading relies on a single class implementing the plugin interface,
 * we have to implement it in the child class, even though the base class satisfies it.
 *
 * Class Formatter
 */
class Formatter extends KeepAChangelogParser {
	use PluginTrait;

	/**
	 * Bullet for changes.
	 *
	 * @var string
	 */
	public $bullet = '-   ';

	/**
	 * Prologue text.
	 *
	 * @var string
	 */
	public $prologue = "# Changelog \n\n";

	/**
	 * Epilogue text.
	 *
	 * @var string
	 */
	public $epilogue = '';

	/**
	 * Entry pattern regex.
	 *
	 * @var string
	 */
	public $entry_pattern = '/^##\s+([^\n=]+)\s+((?:(?!^##).)+)/ms';

	/**
	 * Heading pattern regex.
	 *
	 * @var string
	 */
	public $heading_pattern = '/^## \[+(\[?[^] ]+\]?)\]\(.+\) - (.+?)\n/s';

	/**
	 * Subheading pattern regex.
	 *
	 * @var string
	 */
	public $subentry_pattern = '/^###(.+)\n/m';

	/**
	 * Return the epiologue.
	 */
	public function getEpilogue() {
		return $this->epilogue;
	}

	/**
	 * Get Release link given a version number.
	 *
	 * @throws \InvalidArgumentException When directory parsing fails.
	 * @param string $version Release version.
	 *
	 * @return string Link to the version's release.
	 */
	public function getReleaseLink( $version ) {
		return 'https://github.com/woocommerce/woocommerce/releases/tag/' . $version;
	}

	/**
	 * Modified version of parse() from KeepAChangelogParser.
	 *
	 * @param string $changelog Changelog contents.
	 * @return Changelog
	 * @throws \InvalidArgumentException If the changelog data cannot be parsed.
	 */
	public function parse( $changelog ) {
		$ret = new Changelog();

		// Fix newlines and expand tabs.
		$changelog = strtr( $changelog, array( "\r\n" => "\n" ) );
		$changelog = strtr( $changelog, array( "\r" => "\n" ) );
		while ( strpos( $changelog, "\t" ) !== false ) {
			$changelog = preg_replace_callback(
				'/^([^\t\n]*)\t/m',
				function ( $m ) {
					return $m[1] . str_repeat( ' ', 4 - ( mb_strlen( $m[1] ) % 4 ) );
				},
				$changelog
			);
		}

		// Entries make up the rest of the document.
		$entries       = array();
		$entry_pattern = $this->entry_pattern;
		preg_match_all( $entry_pattern, $changelog, $matches );

		foreach ( $matches[0] as $section ) {
			// Remove the epilogue, if it exists.
			$section = str_replace( $this->epilogue, '', $section );

			$heading_pattern  = $this->heading_pattern;
			$subentry_pattern = $this->subentry_pattern;

			// Parse the heading and create a ChangelogEntry for it.
			preg_match( $heading_pattern, $section, $heading );

			// Check if the heading may be a sub-heading.
			preg_match( $subentry_pattern, $section, $subheading );
			$is_subentry = count( $subheading ) > 0;

			if ( ! count( $heading ) && ! count( $subheading ) ) {
				throw new \InvalidArgumentException( 'Invalid heading' );
			}

			$version         = '';
			$timestamp       = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );
			$entry_timestamp = new \DateTime( 'now', new \DateTimeZone( 'UTC' ) );

			if ( count( $heading ) ) {
				$version   = $heading[1];
				$timestamp = $heading[2];

				try {
					$timestamp = new \DateTime( $timestamp, new \DateTimeZone( 'UTC' ) );
				} catch ( \Exception $ex ) {
					throw new \InvalidArgumentException( "Heading has an invalid timestamp: $heading", 0, $ex );
				}

				if ( strtotime( $heading[2], 0 ) !== strtotime( $heading[2], 1000000000 ) ) {
					throw new \InvalidArgumentException( "Heading has a relative timestamp: $heading" );
				}
				$entry_timestamp = $timestamp;

				$content = trim( preg_replace( $heading_pattern, '', $section ) );
			} elseif ( $is_subentry ) {
				// It must be a subheading.
				$version = $subheading[0]; // For now.
				$content = trim( preg_replace( $subentry_pattern, '', $section ) );
			}

			$entry = $this->newChangelogEntry(
				$version,
				array(
					'timestamp' => $timestamp,
				)
			);

			$entries[] = $entry;

			if ( '' === $content ) {
				// Huh, no changes.
				continue;
			}

			// Now parse all the subheadings and changes.
			while ( '' !== $content ) {
				$changes = array();
				$rows    = explode( "\n", $content );
				foreach ( $rows as $row ) {
					$row          = trim( $row );
					$row          = preg_replace( '/' . $this->bullet . '/', '', $row, 1 );
					$row_segments = explode( ' - ', $row );
					$significance = trim( strtolower( $row_segments[0] ) );

					array_push(
						$changes,
						array(
							'subheading'   => $is_subentry ? '' : trim( $row_segments[0] ),
							'content'      => $is_subentry ? trim( $row ) : trim( isset( $row_segments[1] ) ? $row_segments[1] : '' ),
							'significance' => in_array( $significance, array( 'patch', 'minor', 'major' ), true ) ? $significance : null,
						)
					);
				}

				foreach ( $changes as $change ) {
					$entry->appendChange(
						$this->newChangeEntry(
							array(
								'subheading'   => $change['subheading'],
								'content'      => $change['content'],
								'significance' => $change['significance'],
								'timestamp'    => $entry_timestamp,
							)
						)
					);
				}
				$content = '';
			}
		}

		$ret->setEntries( $entries );
		$ret->setPrologue( $this->prologue );
		$ret->setEpilogue( $this->getEpilogue() );
		return $ret;
	}

	/**
	 * Write a Changelog object to a string.
	 *
	 * @param Changelog $changelog Changelog object.
	 * @return string
	 */
	public function format( Changelog $changelog ) {
		$ret         = '';
		$date_format = 'Y-m-d';
		$bullet      = $this->bullet;
		$indent      = str_repeat( ' ', strlen( $bullet ) );

		$prologue = trim( $changelog->getPrologue() );
		if ( '' !== $prologue ) {
			$ret .= "$prologue\n\n";
		}

		foreach ( $changelog->getEntries() as $entry ) {
			$version      = $entry->getVersion();
			$is_subentry  = preg_match( $this->subentry_pattern, $version, $subentry );
			$timestamp    = $entry->getTimestamp();
			$release_link = $this->getReleaseLink( $version );

			if ( $is_subentry ) {
				$ret .= '###' . $subentry[1] . " \n\n";
			} else {
				$ret .= '## [' . $version . '](' . $release_link . ') - ' . $timestamp->format( $date_format ) . " \n\n";
			}

			$prologue = trim( $entry->getPrologue() );

			if ( '' !== $prologue ) {
				$ret .= "$prologue\n\n";
			}

			foreach ( $entry->getChangesBySubheading() as $heading => $changes ) {
				foreach ( $changes as $change ) {
					$significance    = $change->getSignificance();
					$breaking_change = 'major' === $significance ? ' [ **BREAKING CHANGE** ]' : '';
					$text            = trim( $change->getContent() );
					if ( '' !== $text ) {
						$preamble = $is_subentry ? '' : $bullet . ucfirst( $significance ) . $breaking_change . ' - ';
						$ret     .= $preamble . str_replace( "\n", "\n$indent", $text ) . "\n";
					}
				}
			}
			$ret = trim( $ret ) . "\n\n";
		}

		$epilogue = trim( $changelog->getEpilogue() );
		if ( '' !== $epilogue ) {
			$ret .= "$epilogue\n";
		}

		$ret = trim( $ret ) . "\n";

		return $ret;
	}
}
