<?php

namespace Automattic\WooCommerce\Tests\Internal\Utilities;

use Automattic\WooCommerce\Internal\Utilities\URL;
use WC_Unit_Test_Case;

/**
 * A collection of tests for the filepath utility class.
 */
class URLTest extends WC_Unit_Test_Case {
	public function test_if_absolute_or_relative() {
		$this->assertTrue(
			( new URL( '/etc/foo/bar' ) )->is_absolute() ,
			'Correctly determines if a Unix-style path is absolute.'
		);

		$this->assertTrue(
			( new URL( 'c:\\Windows\Programs\Item' ) )->is_absolute(),
			'Correctly determines if a Windows-style path is absolute.'
		);

		$this->assertTrue(
			( new URL( 'wp-content/uploads/thing.pdf' ) )->is_relative(),
			'Correctly determines if a filepath is relative.'
		);
	}

	public function test_directory_traversal_resolution() {
		$this->assertEquals(
			'/var/foo/foobar',
			( new URL( '/var/foo/bar/baz/../../foobar' ) )->get_path(),
			'Correctly resolves a path containing a directory traversal.'
		);

		$this->assertEquals(
			'/bazbar',
			( new URL( '/var/foo/../../../../bazbar' ) )->get_path(),
			'Correctly resolves a path containing a directory traversal, even if the traversals attempt to backtrack beyond the root directory.'
		);

		$this->assertEquals(
			'../should/remain/relative',
			( new URL( 'relative/../../should/remain/relative' ) )->get_path(),
			'Simplifies a relative path containing directory traversals to the extent possible (without inspecting the filesystem - scenario #1).'
		);

		$this->assertEquals(
			'../../should/remain/relative',
			( new URL( 'relative/../../../should/remain/relative' ) )->get_path(),
			'Simplifies a relative path containing directory traversals to the extent possible (without inspecting the filesystem - scenario #2).'
		);

		$this->assertEquals(
			'file:///foo/bar/baz',
			( new URL( '/../foo/bar/baz/bazooka/../../baz' ) )->get_url(),
			'Directory traversals are appropriately resolved even in complex cases with multiple separate traversals. When the original path is absolute, the output will be absolute.'
		);
	}

	public function test_can_get_normalized_string_representation() {
		$this->assertEquals(
			'foo/bar/baz',
			( new URL( 'foo/bar//baz' ) )->get_path(),
			'Empty segments are discarded, remains as a relative path.'
		);

		$this->assertEquals(
			'/foo/  /bar/   /baz/foobarbaz',
			( new URL( '///foo/  /bar/   /baz//foobarbaz' ) )->get_path(),
			'Empty segments are discarded, non-empty segments containing only whitespace are preserved, remains as an absolute path.'
		);

		$this->assertEquals(
			'c:/Windows/Server/HTTP/dump.xml',
			( new URL( 'c:\\Windows\Server\HTTP\dump.xml' ) )->get_path(),
			'String representations of Windows filepaths have forward slash separators and preserve the drive letter.'
		);
	}

	public function test_can_get_normalized_url_representation() {
		$this->assertEquals(
			'file://relative/path',
			( new URL( 'relative/path' ) )->get_url(),
			'Can obtain a URL representation of a relative filepath, even when the initial string was a plain filepath.'
		);

		$this->assertEquals(
			'file:///absolute/path',
			( new URL( '/absolute/path' ) )->get_url(),
			'Can obtain a URL representation of an absolute filepath, even when the initial string was a plain filepath.'
		);

		$this->assertEquals(
			'file:///etc/foo/bar',
			( new URL( 'file:///etc/foo/bar' ) )->get_url(),
			'Can obtain a URL representation of a filepath, when the source filepath was also expressed as a URL.'
		);
	}

	public function test_handling_of_percent_encoded_periods() {
		$this->assertEquals(
			'https://foo.bar/asset.txt',
			( new URL( 'https://foo.bar/parent/.%2e/asset.txt' ) )->get_url(),
			'Directory traversals expressed using percent-encoding are still resolved (lowercase, one encoded period).'
		);

		$this->assertEquals(
			'https://foo.bar/asset.txt',
			( new URL( 'https://foo.bar/parent/%2E./asset.txt' ) )->get_url(),
			'Directory traversals expressed using percent-encoding are still resolved (uppercase, one encoded period).'
		);

		$this->assertEquals(
			'https://foo.bar/asset.txt',
			( new URL( 'https://foo.bar/parent/%2E%2e/asset.txt' ) )->get_url(),
			'Directory traversals expressed using percent-encoding are still resolved (mixed case, both periods encoded).'
		);

		$this->assertEquals(
			'https://foo.bar/parent/%2E.%2fasset.txt',
			( new URL( 'https://foo.bar/parent/%2E.%2fasset.txt' ) )->get_url(),
			'If the forward slash after a double period is URL encoded, there is no directory traversal (since this means the slash is a part of the segment and is not a separator).'
		);

		$this->assertEquals(
			'file:///var/www/network/%2econfig',
			( new URL( '/var/www/network/%2econfig' ) )->get_url(),
			'Use of percent-encoding in URLs is accepted and unnecessary conversion does not take place.'
		);
	}

	public function test_can_obtain_parent_url() {
		$this->assertFalse(
			( new URL( '/' ) )->get_parent_url(),
			'Root directory "/" is considered to have no parent (scenario #1).'
		);

		$this->assertFalse(
			( new URL( '/' ) )->get_parent_url( 2 ),
			'Root directory "/" is considered to have no parent (scenario #2).'
		);

		$this->assertEquals(
			'file:///var/',
			( new URL( '/var/dev/' ) )->get_parent_url(),
			'The parent URL will be trailingslashed.'
		);

		$this->assertFalse(
			( new URL( 'https://example.com' ) )->get_parent_url(),
			'In the case of non-file URLs, if we only have a host name and no path then the parent cannot be derived.'
		);
	}

	public function test_can_obtain_all_parent_urls() {
		$this->assertEquals(
			array(
				'https://local.web/wp-content/uploads/woocommerce_uploads/pdf_bucket/',
				'https://local.web/wp-content/uploads/woocommerce_uploads/',
				'https://local.web/wp-content/uploads/',
				'https://local.web/wp-content/',
				'https://local.web/',
			),
			( new URL( 'https://local.web/wp-content/uploads/woocommerce_uploads/pdf_bucket/secret-sauce.pdf' ) )->get_all_parent_urls(),
			'All parent URLs can be derived, but the host name is never stripped.'
		);

		$this->assertEquals(
			array(
				'file:///srv/websites/my.wp.site/public/',
				'file:///srv/websites/my.wp.site/',
				'file:///srv/websites/',
				'file:///srv/',
				'file:///',
			),
			( new URL( '/srv/websites/my.wp.site/public/test-file.doc' ) )->get_all_parent_urls(),
			'All parent URLs can be derived for a filepath, up to and including the root directory.'
		);

		$this->assertEquals(
			array(
				'file://C:/Documents/Web/TestSite/',
				'file://C:/Documents/Web/',
				'file://C:/Documents/',
				'file://C:/',
			),
			( new URL( 'C:\\Documents\\Web\\TestSite\\BackgroundTrack.mp3' ) )->get_all_parent_urls(),
			'All parent URLs can be derived for a filepath, up to and including the root directory plus drive letter (Windows).'
		);
	}

	public function test_obtaining_parent_urls_from_relative_urls() {
		$this->assertEquals(
			array(
				'file://relative/to/',
				'file://relative/',
				'file://./',
			),
			( new URL( 'relative/to/abspath' ) )->get_all_parent_urls(),
			'When obtaining all parent URLs for a relative filepath, we never return the root directory and never return a URL containing traversals. '
		);

		$this->assertEquals(
			array(
				'file://../../'
			),
			( new URL( '../../some.file' ) )->get_all_parent_urls(),
			'When obtaining all parent URLs for a path that begins with directory traversals, we only go up one more level.'
		);

		$this->assertEquals(
			'file://../../',
			( new URL( '../' ) )->get_parent_url(),
			'If a relative *directory* beginning with a traversal is provided, we can successfully derive its parent (scenario #1).'
		);

		$this->assertEquals(
			'file://../../../',
			( new URL( '../' ) )->get_parent_url( 2 ),
			'If a relative *directory* beginning with a traversal is provided, we can successfully derive its parent (scenario #2).'
		);

		$this->assertEquals(
			'file://../../../',
			( new URL( '../../some.file' ) )->get_parent_url( 2 ),
			'If the grandparent of a relative path that begins with one or more traversals is requested, we should receive the expected result (scenario #1).'
		);

		$this->assertEquals(
			'file://../../../../',
			( new URL( '../../some.file' ) )->get_parent_url( 3 ),
			'If the grandparent of a relative path that begins with one or more traversals is requested, we should receive the expected result (scenario #2).'
		);

		$this->assertEquals(
			'file://./',
			( new URL( 'just-a-file.png' ) )->get_parent_url(),
			'The parent URL of an unqualified, relative file is simply an empty relative path (generally, though not always, this is equivalent to ABSPATH).'
		);

		$this->assertEquals(
			'file://../../',
			( new URL( '../../relatively-placed-file.pdf' ) )->get_parent_url()
		);

		$this->assertEquals(
			'file://../',
			( new URL( 'relatively-placed-file.pdf' ) )->get_parent_url( 2 ),
			'For filepaths, we can successfully determine the (grand-)parent directories of relative filepaths (when explicitly requested).'
		);

		$this->assertEquals(
			'file://../../',
			( new URL( 'relatively-placed-file.pdf' ) )->get_parent_url( 3 ),
			'For filepaths, we can successfully determine the (grand-)parent directories of relative filepaths (when explicitly requested).'
		);

		$this->assertEquals(
			'file://../foo/bar/baz',
			( new URL( '../foo/bar/cat/dog/../../baz' ) )->get_url(),
			'Directory traversals are appropriately resolved even in complex cases with multiple separate traversals.'
		);
	}
}
