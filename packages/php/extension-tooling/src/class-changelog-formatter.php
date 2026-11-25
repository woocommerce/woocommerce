<?php
/**
 * Changelog_Formatter class
 *
 * @package WooCommerce\ExtensionChangelogger
 */

namespace Automattic\WooCommerce\ExtensionTools\Changelogger;

use Automattic\Jetpack\Changelog\Changelog;
use Automattic\Jetpack\Changelog\ChangelogEntry;
use Automattic\Jetpack\Changelog\Parser;
use Automattic\Jetpack\Changelogger\FormatterPlugin;
use Automattic\Jetpack\Changelogger\PluginTrait;
use DateTime;
use DateTimeZone;
use InvalidArgumentException;

/**
 * Jetpack Changelogger formatter for the legacy `changelog.txt` format.
 *
 * The format is a title line, then one section per release:
 *
 *     *** Changelog ***
 *
 *     = 1.5.1 - 2026-09-17 =
 *     * Fix - Something that was broken.
 *     * Tweak - Something that was adjusted.
 *
 * Version headings use the `= X.Y.Z - YYYY-MM-DD =` shape WordPress.org uses, which
 * WooCommerce.com reads too.
 *
 * The entry type lives inline on each bullet rather than under a subheading, so it is
 * carried on the change entry's subheading and re-emitted verbatim. Nothing is mapped
 * back through the configured types on output: a decade of history contains types we
 * no longer issue (such as `Feature` and `Update`), and rewriting them would turn every
 * release into a changelog-wide diff.
 */
class Changelog_Formatter extends Parser implements FormatterPlugin {

	use PluginTrait;

	/**
	 * Date string used for an entry with no release date.
	 *
	 * @var string
	 */
	const UNRELEASED = 'unreleased';

	/**
	 * Bullet prefix for change lines.
	 *
	 * @var string
	 */
	const BULLET = '* ';

	/**
	 * Indentation for lines that continue a change bullet.
	 *
	 * @var string
	 */
	const CONTINUATION = '  ';

	/**
	 * Separator between a change's type and its content.
	 *
	 * @var string
	 */
	const SEPARATOR = ' - ';

	/**
	 * Output date format.
	 *
	 * @var string
	 */
	const DATE_FORMAT = 'Y-m-d';

	/**
	 * Matches a version heading, e.g. `= 1.5.1 - 2026-09-17 =`.
	 *
	 * Month and day are zero-padded only, the shape WordPress.org and the WooCommerce.com
	 * changelog parser read. The two-part versions of the earliest releases are accepted.
	 * The version must be numeric: a placeholder version is otherwise parsed as a real
	 * release and written back on the next run.
	 *
	 * @var string
	 */
	const HEADING_PATTERN = '/^= (\d+(?:\.\d+){1,2}(?:[-+]\S+)?) - (\d{4})-(\d{2})-(\d{2}) =$/';

	/**
	 * Title line written above the entries when the changelog file has none yet.
	 *
	 * @var string
	 */
	private $title = '';

	/**
	 * Constructor.
	 *
	 * @param array $config Configuration from `extra.changelogger.formatter` in composer.json. Recognized keys:
	 *  - title: (string) Title line for the changelog file. Default none.
	 */
	public function __construct( array $config = array() ) {
		if ( isset( $config['title'] ) && is_string( $config['title'] ) ) {
			$this->title = $config['title'];
		}
	}

	/**
	 * Parse changelog data into a Changelog object.
	 *
	 * @param string $changelog Changelog contents.
	 * @return Changelog
	 * @throws InvalidArgumentException If a version heading carries an invalid date.
	 */
	public function parse( $changelog ) {
		$ret = new Changelog();

		$changelog       = self::normalize_line_endings( $changelog );
		$prologue        = array();
		$entries         = array();
		$entry           = null;
		$change          = null;
		$first_line_tail = '';

		foreach ( explode( "\n", $changelog ) as $raw_line ) {
			$line    = trim( $raw_line );
			$heading = $this->parse_heading( $line );

			if ( null !== $heading ) {
				$entry = $this->newChangelogEntry(
					$heading['version'],
					array(
						'timestamp'    => $heading['timestamp'],
						'rawTimestamp' => $heading['date'],
					)
				);

				$entries[] = $entry;
				$change    = null;
				continue;
			}

			$is_bullet = 0 === strpos( $line, self::BULLET );

			// A near-miss heading is never a change: absorbed into the entry above it, its
			// own changes would merge into that release and the section would be gone on the
			// next write. Fail instead. That is any line starting with a single `=`, and any
			// heading in the older WooCommerce.com shape (`YYYY.MM.DD - version X.Y.Z`).
			// A title starting with `==` is not a heading.
			if ( ! $is_bullet && self::looks_like_heading( $line ) ) {
				throw new InvalidArgumentException( "Line looks like a version heading but is not one: $line" );
			}

			if ( null !== $change && 0 === strpos( $raw_line, self::CONTINUATION ) ) {
				$continuation = substr( $raw_line, strlen( self::CONTINUATION ) );
				// A bullet at the continuation margin starts a legacy change. A deeper bullet belongs to the body.
				if ( ! $is_bullet || 0 !== strpos( $continuation, self::BULLET ) ) {
					$change->setContent( $change->getContent() . $first_line_tail . "\n" . $continuation );
					$first_line_tail = '';
					continue;
				}
			}

			if ( '' === $line ) {
				$change = null;
				continue;
			}

			// Anything else ahead of the first heading is the title block, bullets excepted:
			// a bullet there is an orphaned change, not a title line.
			if ( null === $entry ) {
				if ( $is_bullet ) {
					throw new InvalidArgumentException( "Expected a version heading, got: $line" );
				}

				$prologue[] = $line;
				continue;
			}

			$change = $this->newChangeEntry( $this->parse_change( $line, $entry->getTimestamp() ) );
			$entry->appendChange( $change );

			// Continuations only follow the unindented bullets written by format().
			if ( 0 !== strpos( $raw_line, self::BULLET ) ) {
				$change = null;
			} else {
				// Keep a Markdown hard line break only if a continuation follows.
				$first_line_tail = substr( $raw_line, strlen( rtrim( $raw_line, " \t" ) ) );
			}
		}

		$ret->setPrologue( $prologue ? implode( "\n", $prologue ) : $this->title );
		$ret->setEntries( $entries );

		return $ret;
	}

	/**
	 * Write a Changelog object to a string.
	 *
	 * @param Changelog $changelog Changelog object.
	 * @return string
	 * @throws InvalidArgumentException If a continuation looks like a separate changelog entry.
	 */
	public function format( Changelog $changelog ) {
		$ret = '';

		$prologue = trim( $changelog->getPrologue() );

		if ( '' === $prologue ) {
			$prologue = trim( $this->title );
		}

		if ( '' !== $prologue ) {
			$ret .= $prologue . "\n\n";
		}

		foreach ( $changelog->getEntries() as $entry ) {
			$ret .= $this->format_heading( $entry ) . "\n";

			foreach ( $entry->getChanges() as $change ) {
				$line = $this->format_change( $change->getSubheading(), $change->getContent() );

				if ( '' === $line ) {
					continue;
				}

				$ret .= self::BULLET . str_replace( "\n", "\n" . self::CONTINUATION, $line ) . "\n";
			}

			$ret = rtrim( $ret ) . "\n\n";
		}

		return rtrim( $ret ) . "\n";
	}

	/**
	 * Create a new ChangelogEntry.
	 *
	 * @param string $version See `ChangelogEntry::__construct()`.
	 * @param array  $data See `ChangelogEntry::__construct()`.
	 * @return Changelog_Entry
	 */
	public function newChangelogEntry( $version, $data = array() ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Matches the camelCase API of the parent class.
		return new Changelog_Entry( $version, $data );
	}

	/**
	 * Parse a version heading.
	 *
	 * @param string $line Line to parse.
	 * @return array|null Keys 'date', 'timestamp' and 'version', or null if the line is not a heading.
	 * @throws InvalidArgumentException If the heading carries an invalid date.
	 */
	private function parse_heading( $line ) {
		$matches = array();

		if ( ! preg_match( self::HEADING_PATTERN, $line, $matches ) ) {
			// Preserve the heading for an entry without a release date.
			if ( preg_match( '/^= (\d+(?:\.\d+){1,2}(?:[-+]\S+)?) - ' . preg_quote( self::UNRELEASED, '/' ) . ' =$/', $line, $matches ) ) {
				return array(
					'date'      => self::UNRELEASED,
					'timestamp' => null,
					'version'   => $matches[1],
				);
			}

			return null;
		}

		list( , $version, $year, $month, $day ) = $matches;

		$date      = sprintf( '%04d-%d-%d 00:00:00', $year, $month, $day );
		$timestamp = DateTime::createFromFormat( 'Y-n-j H:i:s', $date, new DateTimeZone( 'UTC' ) );

		// createFromFormat() rolls overflowing values over instead of failing, so compare it back.
		if ( false === $timestamp || $timestamp->format( 'Y-n-j' ) !== sprintf( '%04d-%d-%d', $year, $month, $day ) ) {
			throw new InvalidArgumentException( "Heading has an invalid date: $line" );
		}

		return array(
			'date'      => $year . '-' . $month . '-' . $day,
			'timestamp' => $timestamp,
			'version'   => $version,
		);
	}

	/**
	 * Parse a change line into ChangeEntry data.
	 *
	 * @param string        $line Line to parse.
	 * @param DateTime|null $timestamp Timestamp of the entry the change belongs to.
	 * @return array
	 */
	private function parse_change( $line, $timestamp ) {
		$content = trim( $line );

		// Strip the bullet as a literal prefix only. A later occurrence is part of the text.
		if ( 0 === strpos( $content, self::BULLET ) ) {
			$content = substr( $content, strlen( self::BULLET ) );
		}

		$subheading = '';
		$segments   = explode( self::SEPARATOR, $content, 2 );

		// A handful of entries from before the format settled carry no type at all.
		if ( 2 === count( $segments ) ) {
			$subheading = trim( $segments[0] );
			$content    = $segments[1];
		}

		return array(
			'subheading' => $subheading,
			'content'    => trim( $content ),
			'timestamp'  => null === $timestamp ? 'now' : $timestamp,
		);
	}

	/**
	 * Format a change as a line, without its bullet.
	 *
	 * @param string $subheading Change type.
	 * @param string $content Change content.
	 * @return string Empty string if there is nothing to write.
	 * @throws InvalidArgumentException If a continuation looks like a separate changelog entry.
	 */
	private function format_change( $subheading, $content ) {
		$subheading = trim( (string) $subheading );
		$content    = trim( self::normalize_line_endings( $content ) );

		if ( false !== strpos( $content, "\n" ) ) {
			foreach ( array_slice( explode( "\n", $content ), 1 ) as $continuation ) {
				$line = trim( $continuation );
				if ( 0 === strpos( rtrim( $continuation ), self::BULLET ) || self::looks_like_heading( $line ) ) {
					throw new InvalidArgumentException( "Change continuation looks like a separate changelog entry: $line" );
				}
			}
		}

		if ( '' === $subheading ) {
			return $content;
		}

		if ( '' === $content ) {
			return '';
		}

		return $subheading . self::SEPARATOR . $content;
	}

	/**
	 * Normalize CRLF and bare CR before classifying or indenting lines.
	 *
	 * @param string $text Source text.
	 * @return string
	 */
	private static function normalize_line_endings( $text ) {
		return str_replace( array( "\r\n", "\r" ), "\n", (string) $text );
	}

	/**
	 * Whether the line begins like a current or legacy release heading.
	 *
	 * @param string $line Trimmed line.
	 * @return bool
	 */
	private static function looks_like_heading( $line ) {
		return preg_match( '/^=(?!=)/', $line ) || preg_match( '/^\d{4}[.-]\d{1,2}[.-]\d{1,2}\h+-\h+version\b/i', $line );
	}

	/**
	 * Format a version heading.
	 *
	 * @param ChangelogEntry $entry Entry to format the heading for.
	 * @return string
	 */
	private function format_heading( ChangelogEntry $entry ) {
		$date = $entry instanceof Changelog_Entry ? $entry->getRawTimestamp() : null;

		if ( null === $date ) {
			$timestamp = $entry->getTimestamp();
			$date      = null === $timestamp ? self::UNRELEASED : $timestamp->format( self::DATE_FORMAT );
		}

		return '= ' . $entry->getVersion() . self::SEPARATOR . $date . ' =';
	}
}
