<?php
/**
 * Changelog formatter tests.
 *
 * @package WooCommerce\ExtensionChangelogger
 */

declare( strict_types = 1 );

use Automattic\Jetpack\Changelog\Changelog;
use PHPUnit\Framework\TestCase;
use Automattic\WooCommerce\ExtensionTools\Changelogger\Changelog_Entry;
use Automattic\WooCommerce\ExtensionTools\Changelogger\Changelog_Formatter;

/**
 * Tests the Jetpack Changelogger formatter that reads and writes changelog.txt.
 *
 * The formatter rewrites the whole file on every release, so the tests that matter
 * are the ones asserting that nothing but the new entry changes.
 */
class ChangelogFormatterTest extends TestCase {

	/**
	 * The formatter under test.
	 *
	 * @var Changelog_Formatter
	 */
	private $formatter;

	/**
	 * Set up the formatter with the fixture title.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->formatter = new Changelog_Formatter( array( 'title' => $this->configured_title() ) );
	}

	/**
	 * Title supplied as `extra.changelogger.formatter.title` by an extension.
	 *
	 * @return string
	 */
	private function configured_title() {
		return '*** Extension Changelog ***';
	}

	/**
	 * Path to the changelog fixture.
	 *
	 * @return string
	 */
	private function changelog_path() {
		return __DIR__ . '/fixtures/changelog.txt';
	}

	/**
	 * Parse then format, for round-trip assertions.
	 *
	 * @param string $changelog Changelog contents.
	 * @return string
	 */
	private function round_trip( $changelog ) {
		return $this->formatter->format( $this->formatter->parse( $changelog ) );
	}

	/**
	 * The changelog fixture must survive a parse/format cycle byte for byte.
	 *
	 * Everything else here is a special case of this: if it fails, the next release
	 * rewrites years of history in the release pull request.
	 */
	public function test_fixture_changelog_round_trips_unchanged() {
		$changelog = file_get_contents( $this->changelog_path() );

		$this->assertNotEmpty( $changelog );
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * The changelog fixture must parse into entries, not silently into one blob.
	 */
	public function test_fixture_changelog_parses_into_entries() {
		$contents  = file_get_contents( $this->changelog_path() );
		$changelog = $this->formatter->parse( $contents );

		$this->assertSame( $this->configured_title(), $changelog->getPrologue() );
		$this->assertSame( array( '1.5.1', '1.0.0' ), $changelog->getVersions() );
		$this->assertNotEmpty( $changelog->getLatestEntry()->getChanges() );
	}

	/**
	 * A heading carries the release date and the version; a bullet carries its type.
	 */
	public function test_parses_headings_and_typed_entries() {
		$changelog = $this->formatter->parse(
			"*** Title ***\n\n"
			. "= 1.5.1 - 2026-09-17 =\n"
			. "* Fix - Fixed a thing.\n"
			. "* Tweak - Tweaked a thing.\n"
		);

		$entries = $changelog->getEntries();

		$this->assertCount( 1, $entries );
		$this->assertSame( '1.5.1', $entries[0]->getVersion() );
		$this->assertSame( '2026-09-17', $entries[0]->getTimestamp()->format( 'Y-m-d' ) );

		$changes = $entries[0]->getChanges();

		$this->assertCount( 2, $changes );
		$this->assertSame( 'Fix', $changes[0]->getSubheading() );
		$this->assertSame( 'Fixed a thing.', $changes[0]->getContent() );
		$this->assertSame( 'Tweak', $changes[1]->getSubheading() );
	}

	/**
	 * Only the first ' - ' separates the type from the entry.
	 */
	public function test_entry_content_may_contain_the_separator() {
		$changelog = $this->formatter->parse(
			"*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Saved settings - and their defaults - are restored.\n"
		);

		$change = $changelog->getEntries()[0]->getChanges()[0];

		$this->assertSame( 'Fix', $change->getSubheading() );
		$this->assertSame( 'Saved settings - and their defaults - are restored.', $change->getContent() );
	}

	/**
	 * Legacy shapes changelogs still carry must round-trip untouched: bullets with no
	 * type at all, types we no longer issue, and types written with a colon or an en dash.
	 *
	 * @dataProvider provide_legacy_shapes
	 *
	 * @param string $changelog Changelog contents.
	 */
	public function test_legacy_shapes_round_trip_unchanged( $changelog ) {
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * Legacy changelog shapes.
	 *
	 * @return array
	 */
	public function provide_legacy_shapes() {
		return array(
			'bullet with no type'    => array( "*** Title ***\n\n= 1.0.0 - 2021-10-25 =\n* Initial release.\n" ),
			'retired type New'       => array( "*** Title ***\n\n= 1.1.0 - 2022-01-10 =\n* New - Settings link to plugin action links.\n" ),
			'retired type Feature'   => array( "*** Title ***\n\n= 1.1.0 - 2022-01-10 =\n* Feature - Introduced a filter.\n" ),
			'retired type Important' => array( "*** Title ***\n\n= 1.1.1 - 2022-02-16 =\n* Important - Declared support for WooCommerce 6.2.\n" ),
			'retired type Update'    => array( "*** Title ***\n\n= 1.1.1 - 2022-02-16 =\n* Update - Updated the settings screen copy.\n" ),
			'colon in the type'      => array( "*** Title ***\n\n= 1.1.1 - 2022-02-16 =\n* Feature: Added support for a third-party plugin.\n" ),
			'en dash in the type'    => array( "*** Title ***\n\n= 1.1.1 - 2022-02-16 =\n* Fix – Fixed the changelog for the 1.1.0 release.\n" ),
			'several entries'        => array( "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Second.\n\n= 1.5.0 - 2026-09-09 =\n* Add - First.\n" ),
		);
	}

	/**
	 * A new release gets a zero-padded date, and writing it must not reformat the
	 * headings below it.
	 */
	public function test_new_entry_leaves_legacy_dates_alone() {
		$changelog = $this->formatter->parse( "*** Title ***\n\n= 1.0.0 - 2021-10-25 =\n* Fix - Fixed a thing.\n" );

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.5.2',
				array(
					'timestamp' => new DateTime( '2026-09-01', new DateTimeZone( 'UTC' ) ),
					'changes'   => array(
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Add',
								'content'    => 'Added a thing.',
							)
						),
					),
				)
			)
		);

		$this->assertSame(
			"*** Title ***\n\n= 1.5.2 - 2026-09-01 =\n* Add - Added a thing.\n\n= 1.0.0 - 2021-10-25 =\n* Fix - Fixed a thing.\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * A change file may carry an empty entry when its significance is 'patch'. It must
	 * compile to no bullet at all, rather than to a dangling '* Fix - ' that the next
	 * parse would read back as an untyped change.
	 */
	public function test_a_change_with_no_content_writes_no_line() {
		$changelog = new Changelog();

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.5.2',
				array(
					'timestamp' => new DateTime( '2026-09-01', new DateTimeZone( 'UTC' ) ),
					'changes'   => array(
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Fix',
								'content'    => '',
							)
						),
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Tweak',
								'content'    => 'Tweaked a thing.',
							)
						),
					),
				)
			)
		);

		$this->assertSame(
			$this->configured_title() . "\n\n= 1.5.2 - 2026-09-01 =\n* Tweak - Tweaked a thing.\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * With no changelog file to read a title from, the configured one is written.
	 */
	public function test_configured_title_is_used_when_the_changelog_is_empty() {
		$changelog = new Changelog();

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.0.0',
				array(
					'timestamp' => new DateTime( '2021-10-25', new DateTimeZone( 'UTC' ) ),
					'changes'   => array( $this->formatter->newChangeEntry( array( 'content' => 'Initial release.' ) ) ),
				)
			)
		);

		$this->assertSame(
			$this->configured_title() . "\n\n= 1.0.0 - 2021-10-25 =\n* Initial release.\n",
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * A bullet ahead of the first heading means the file is malformed. Absorbing it
	 * into the title would delete a release section on the next write.
	 */
	public function test_parse_rejects_a_bullet_before_the_first_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n* Fix - Orphaned entry.\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A heading with a placeholder date is not a valid heading, and must not be
	 * quietly folded into the title either.
	 */
	public function test_parse_rejects_a_placeholder_date() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.5.1 - 2026-xx-xx =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A heading with a placeholder version is not a valid heading either.
	 */
	public function test_parse_rejects_a_placeholder_version() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= {$this->placeholder_version()} - 2026-08-19 =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * An entry with no release date is round-tripped, but not with a placeholder version.
	 */
	public function test_parse_rejects_a_placeholder_version_on_an_unreleased_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= {$this->placeholder_version()} - unreleased =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * The heading the previous release flow left on top of the file, with both
	 * placeholders, must not be quietly folded into the title. The date alone rejects
	 * it; this pins the exact legacy string.
	 */
	public function test_parse_rejects_the_legacy_placeholder_heading() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= {$this->placeholder_version()} - 2026-xx-xx =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * A near-miss heading below the first release must not be read as one of that
	 * release's changes: its own changes would merge upward and the section would be
	 * dropped on the next write.
	 */
	public function test_parse_rejects_a_placeholder_heading_after_the_first_release() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Fixed a thing.\n\n= {$this->placeholder_version()} - 2026-07-01 =\n* Fix - Fixed another thing.\n" );
	}

	/**
	 * The version placeholder, assembled at runtime: the release version bump rewrites
	 * the literal wherever it appears in the repository, fixture data included.
	 *
	 * @return string
	 */
	private function placeholder_version() {
		return implode( '.', array( 'x', 'x', 'x' ) );
	}

	/**
	 * A heading whose date does not exist is an error, not a date rolled over into
	 * the next month.
	 */
	public function test_parse_rejects_an_impossible_date() {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.5.1 - 2026-02-30 =\n* Fix - Fixed a thing.\n" );
	}

	/**
	 * An entry with no release date is written with `unreleased` in place of the date,
	 * and read back as one.
	 */
	public function test_an_entry_with_no_release_date_is_written_unreleased() {
		$changelog = new Changelog();

		$changelog->addEntry(
			$this->formatter->newChangelogEntry(
				'1.5.2',
				array(
					'timestamp' => null,
					'changes'   => array(
						$this->formatter->newChangeEntry(
							array(
								'subheading' => 'Fix',
								'content'    => 'Fixed a thing.',
							)
						),
					),
				)
			)
		);

		$written = $this->formatter->format( $changelog );

		$this->assertSame( $this->configured_title() . "\n\n= 1.5.2 - unreleased =\n* Fix - Fixed a thing.\n", $written );

		$entry = $this->formatter->parse( $written )->getLatestEntry();

		$this->assertSame( '1.5.2', $entry->getVersion() );
		$this->assertNull( $entry->getTimestamp() );
		$this->assertSame( $written, $this->round_trip( $written ) );
	}

	/**
	 * A title starting with `==`, like a readme section, is a title, not a heading.
	 */
	public function test_a_title_starting_with_equals_signs_is_not_a_heading() {
		$title     = '== Changelog - version history ==';
		$changelog = "$title\n\n= 1.0.0 - 2021-10-25 =\n* Initial release.\n";

		$this->assertSame( $title, $this->formatter->parse( $changelog )->getPrologue() );
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * The formatter's continuation lines stay in the same change entry.
	 */
	public function test_multiline_change_round_trips_without_creating_extra_bullets() {
		$changelog = "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n"
			. "* Fix - First line.  \n  Second line.\n  \n    Indented text.\n"
			. "* Add - Separate change.\n\n= 1.5.0 - 2026-09-16 =\n* Initial change.\n";
		$entries   = $this->formatter->parse( $changelog )->getEntries();
		$changes   = $entries[0]->getChanges();

		$this->assertCount( 2, $entries );
		$this->assertCount( 2, $changes );
		$this->assertSame( "First line.  \nSecond line.\n\n  Indented text.", $changes[0]->getContent() );
		$this->assertSame( $changelog, $this->round_trip( $changelog ) );
	}

	/**
	 * Older lists with mixed bullet indentation still parse as separate changes.
	 */
	public function test_legacy_bullets_with_mixed_indentation_stay_separate() {
		foreach ( array( '', '  ' ) as $indent ) {
			$changelog = "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n{$indent}* Fix - First.\n  * Tweak - Second.\n";
			$changes   = $this->formatter->parse( $changelog )->getLatestEntry()->getChanges();

			$this->assertCount( 2, $changes );
			$this->assertSame( 'Tweak', $changes[1]->getSubheading() );
		}
	}

	/**
	 * A deeper bullet stays in the change body while a legacy bullet starts a new change.
	 */
	public function test_nested_body_and_legacy_bullets_keep_their_boundaries() {
		$changelog = "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n"
			. "* Fix - Example:\n      * literal pattern\n  * Tweak - Separate change.\n";
		$changes   = $this->formatter->parse( $changelog )->getLatestEntry()->getChanges();

		$this->assertCount( 2, $changes );
		$this->assertSame( "Example:\n    * literal pattern", $changes[0]->getContent() );
		$this->assertSame( 'Tweak', $changes[1]->getSubheading() );
		$this->assertSame( str_replace( '  * Tweak', '* Tweak', $changelog ), $this->round_trip( $changelog ) );
	}

	/**
	 * A new change with a nested list can be written and reread as one entry.
	 */
	public function test_write_keeps_nested_list_items_in_the_same_change() {
		$original  = "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Supported modes:\n";
		$changelog = $this->formatter->parse( $original );
		$changes   = $changelog->getLatestEntry()->getChanges();
		$content   = "Supported modes:\n* \n  * Automatic\n    * With limits";
		$changes[0]->setContent( $content );

		$written = $this->formatter->format( $changelog );
		$this->assertSame( $original . "  * \n    * Automatic\n      * With limits\n", $written );

		$changes = $this->formatter->parse( $written )->getLatestEntry()->getChanges();
		$this->assertCount( 1, $changes );
		$this->assertSame( $content, $changes[0]->getContent() );
	}

	/**
	 * Change bodies from any platform serialize with the same continuation lines.
	 */
	public function test_write_normalizes_change_line_endings_before_indenting() {
		$original = "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - First.\n";
		$expected = $original . "  Second.\n";
		foreach ( array( "\n", "\r\n", "\r" ) as $separator ) {
			$changelog = $this->formatter->parse( $original );
			$changes   = $changelog->getLatestEntry()->getChanges();
			$changes[0]->setContent( 'First.' . $separator . 'Second.' );
			$this->assertSame( $expected, $this->formatter->format( $changelog ) );
		}
	}

	/**
	 * Preserve legacy entry boundaries by refusing to write ambiguous continuations.
	 *
	 * @dataProvider provide_ambiguous_continuations
	 * @param string $continuation Change line that would split the entry when reparsed.
	 */
	public function test_write_rejects_ambiguous_continuations( $continuation ) {
		$changelog = $this->formatter->parse( "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - First.\n" );
		$changes   = $changelog->getLatestEntry()->getChanges();
		$changes[0]->setContent( "First.\n$continuation" );

		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Change continuation looks like a separate changelog entry' );
		$this->formatter->format( $changelog );
	}

	/**
	 * These continuations would otherwise parse as independent changes or release headings.
	 *
	 * @return array
	 */
	public function provide_ambiguous_continuations() {
		return array(
			'bullet'            => array( '* Another change.' ),
			'heading'           => array( '= 1.5.0 - 2026-09-16 =' ),
			'near heading'      => array( '= Another line.' ),
			'legacy heading'    => array( '2026.09.01  - version 1.1.0' ),
			'uppercase heading' => array( '2026.09.01 - Version 1.1.0' ),
		);
	}

	/**
	 * Setting a release date on an unreleased entry updates the heading.
	 */
	public function test_setting_a_release_date_replaces_the_parsed_unreleased_heading() {
		$original  = "*** Title ***\n\n= 1.5.2 - unreleased =\n* Fix - Kept this change.\n";
		$changelog = $this->formatter->parse( $original );
		$entry     = $changelog->getLatestEntry();
		$this->assertInstanceOf( Changelog_Entry::class, $entry );
		$this->assertSame( 'unreleased', $entry->getRawTimestamp() );
		$entry->setTimestamp( new DateTime( '2026-09-17', new DateTimeZone( 'UTC' ) ) );

		$this->assertSame(
			str_replace( 'unreleased', '2026-09-17', $original ),
			$this->formatter->format( $changelog )
		);
	}

	/**
	 * A line that looks like a version heading but is not one must fail, not be read as
	 * a change of the release above it.
	 *
	 * @dataProvider provide_malformed_headings
	 *
	 * @param string $heading Malformed heading.
	 */
	public function test_parse_rejects_a_malformed_heading( $heading ) {
		$this->expectException( InvalidArgumentException::class );
		$this->formatter->parse( "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Fixed a thing.\n\n$heading\n* Fix - Fixed another thing.\n" );
	}

	/**
	 * An indented malformed heading immediately after a bullet is still invalid.
	 */
	public function test_parse_rejects_an_indented_heading_in_an_open_change() {
		$this->expectException( InvalidArgumentException::class );
		$this->expectExceptionMessage( 'Line looks like a version heading but is not one: = Another line.' );
		$this->formatter->parse( "*** Title ***\n\n= 1.5.1 - 2026-09-17 =\n* Fix - Fixed a thing.\n  = Another line.\n" );
	}

	/**
	 * Malformed headings: broken WordPress.org ones, and the older WooCommerce.com shape
	 * the changelogs used before.
	 *
	 * @return array
	 */
	public function provide_malformed_headings() {
		return array(
			'no date'                           => array( '= 1.1.0 =' ),
			'indented near heading'             => array( '  = Another line.' ),
			'no separator'                      => array( '= 1.1.0 2026-09-01 =' ),
			'unpadded date'                     => array( '= 1.1.0 - 2026-9-1 =' ),
			'dotted date'                       => array( '= 1.1.0 - 2026.09.01 =' ),
			'placeholder date'                  => array( '= 1.1.0 - 2026-xx-xx =' ),
			'impossible date'                   => array( '= 1.1.0 - 2026-02-30 =' ),
			'placeholder version'               => array( "= {$this->placeholder_version()} - 2026-09-01 =" ),
			'placeholder version, unreleased'   => array( "= {$this->placeholder_version()} - unreleased =" ),
			'dotted WooCommerce.com heading'    => array( '2026.09.01 - version 1.1.0' ),
			'uppercase WooCommerce.com heading' => array( '2016.08.03 - Version 2.0.2' ),
			'dashed WooCommerce.com heading'    => array( '2026-09-01 - version 1.1.0' ),
			'spaced WooCommerce.com heading'    => array( '2026.09.01  - version 1.1.0' ),
		);
	}
}
