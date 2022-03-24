<?php

use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\KeepAChangelogParser;
use Automattic\Jetpack\Changelogger\FormatterPlugin;
use Automattic\Jetpack\Changelogger\PluginTrait;

/**
 * Jetpack Changelogger Formatter for WC Admin
 *
 * Class WCAdminFormatter
 */
class WCAdminFormatter extends KeepAChangelogParser implements FormatterPlugin {
	use PluginTrait;

	/**
	 * Bullet for changes.
	 *
	 * @var string
	 */
	private $bullet = '-';

	/**
	 * String used as the date for an unreleased version.
	 *
	 * @var string
	 */
	private $unreleased = '== Unreleased ==';

	/**
	 * Modified version of parse() from KeepAChangelogParser.
	 *
	 * @param string $changelog Changelog contents.
	 * @return Changelog
	 * @throws InvalidArgumentException If the changelog data cannot be parsed.
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
		$entries = array();
		preg_match_all( '/^\=\=\s+([^\n=]+)\s+\=\=((?:(?!^\=\=).)+)/ms', $changelog, $matches );

		foreach ( $matches[0] as $section ) {
			$heading_pattern = '/^== +(\[?[^] ]+\]?) (.+?) ==/';
			// Parse the heading and create a ChangelogEntry for it.
			preg_match( $heading_pattern, $section, $heading );
			if ( ! count( $heading ) ) {
				throw new InvalidArgumentException( "Invalid heading: $heading" );
			}

			$version   = $heading[1];
			$timestamp = $heading[2];
			if ( $timestamp === $this->unreleased ) {
				$timestamp       = null;
				$entry_timestamp = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
			} else {
				try {
					$timestamp = new DateTime( $timestamp, new DateTimeZone( 'UTC' ) );
				} catch ( \Exception $ex ) {
					throw new InvalidArgumentException( "Heading has an invalid timestamp: $heading", 0, $ex );
				}
				if ( strtotime( $heading[2], 0 ) !== strtotime( $heading[2], 1000000000 ) ) {
					throw new InvalidArgumentException( "Heading has a relative timestamp: $heading" );
				}
				$entry_timestamp = $timestamp;
			}

			$entry = $this->newChangelogEntry(
				$version,
				array(
					'timestamp' => $timestamp,
				)
			);

			$entries[] = $entry;
			$content   = trim( preg_replace( $heading_pattern, '', $section ) );

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
					$row = preg_replace( '/' . $this->bullet . '/', '', $row, 1 );
					$row_segments = explode( ':', $row );

					$changes[] = array(
						'subheading' => trim($row_segments[0]),
						'content' => count( $row_segments ) > 1 ? trim($row_segments[1]) : '',
					);
				}

				foreach ( $changes as $change ) {
					$entry->appendChange(
						$this->newChangeEntry(
							array(
								'subheading' => $change['subheading'],
								'content'    => $change['content'],
								'timestamp'  => $entry_timestamp,
							)
						)
					);
				}
				$content = '';
			}
		}

		$ret->setEntries( $entries );

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
		$date_format = 'm/d/Y';
		$bullet      = '- ';
		$indent      = str_repeat( ' ', strlen( $bullet ) );

		foreach ( $changelog->getEntries() as $entry ) {
			$timestamp = $entry->getTimestamp();
			$ret      .= '== ' . $entry->getVersion() . ' ' . $timestamp->format( $date_format ) . " == \n\n";

			$prologue = trim( $entry->getPrologue() );
			if ( '' !== $prologue ) {
				$ret .= "\n$prologue\n\n";
			}

			foreach ( $entry->getChangesBySubheading() as $heading => $changes ) {
				foreach ( $changes as $change ) {
					$text = trim( $change->getContent() );
					if ( '' !== $text ) {
						$ret .= $bullet . $heading . ': ' . str_replace( "\n", "\n$indent", $text ) . "\n";
					}
				}
			}
			$ret = trim( $ret ) . "\n\n";
		}

		$ret = trim( $ret ) . "\n";

		return $ret;
	}
}
