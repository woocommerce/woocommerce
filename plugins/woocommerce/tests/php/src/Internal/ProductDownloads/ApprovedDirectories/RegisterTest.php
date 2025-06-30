<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\ProductDownloads\ApprovedDirectories;

use Automattic\WooCommerce\Internal\ProductDownloads\ApprovedDirectories\Register;
use WC_Unit_Test_Case;

/**
 * Tests for the Product Downloads Whitelist.
 */
class RegisterTest extends WC_Unit_Test_Case {
	/**
	 * @var Register
	 */
	private static $sut;

	/**
	 * Create a Whitelist with representative paths including a URL with a port number,
	 * a regular URL and an absolute filepath.
	 */
	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		static::$sut = new Register();
		static::$sut->add_approved_directory( 'http://localhost:5000' );
		static::$sut->add_approved_directory( 'https://foo.bar/assets/' );
		static::$sut->add_approved_directory( '/absolute/filepath' );
		static::$sut->add_approved_directory( '/absolute/disabled/filepath', false );
		static::$sut->add_approved_directory( 'C:\\Program\\Data\\Assets' );
	}

	/**
	 * @testdox Test a range of filepaths and URLs that we expect to be seen as valid.
	 */
	public function test_valid_paths() {
		$this->assertTrue(
			static::$sut->is_valid_path( 'https://foo.bar/assets/my/nested/document.xml' ),
			'URL validates if it is subordinate to a whitelisted URL path.'
		);

		$this->assertTrue(
			static::$sut->is_valid_path( 'http://localhost:5000/direct-child.doc' ),
			'URL validates if it is subordinate to a whitelisted URL path (including one with a port number).'
		);

		$this->assertTrue(
			static::$sut->is_valid_path( '/absolute/filepath/my-file.pdf' ),
			'Filepath validates if it is subordinate to a whitelisted path (where the path being tested does not explicitly use the "file://" scheme).'
		);

		$this->assertTrue(
			static::$sut->is_valid_path( 'file:///absolute/filepath/my-file.pdf' ),
			'Filepath validates if it is subordinate to a whitelisted path (where the path being tested explicitly uses the "file://" scheme).'
		);

		$this->assertTrue(
			static::$sut->is_valid_path( 'C:\\Program\\Data\\Assets\\downloadable.exe' ),
			'Filepath validates if it is subordinate to a whitelisted path (Windows-style path complete with drive letter).'
		);

		$this->assertTrue(
			static::$sut->is_valid_path( 'C:\\Program\\Data/Assets/downloadable.exe' ),
			'Filepath validates if it is subordinate to a whitelisted path (Windows-style path complete with drive letter, but with Unix style separators).'
		);

		$this->assertTrue(
			static::$sut->is_valid_path( '/absolute/filepath/../filepath/good.xml' ),
			'Filepaths containing directory traversals validate so long as the resolved path is still subordinate to a whitelisted path.'
		);
	}

	/**
	 * @testdox Test a range of filepaths and URLs that we expect to be seen as invalid.
	 */
	public function test_invalid_paths() {
		$this->assertFalse(
			static::$sut->is_valid_path( 'http://foo.bar/assets/my/nested/document.xml' ),
			'URL should not validate as the ("http://") scheme means it is different to any of the current whitelisted paths.'
		);

		$this->assertFalse(
			static::$sut->is_valid_path( 'http://localhost:50001/some-file.pdf' ),
			'URL should not validate as the port number means it belongs to a distinct, non-whitelisted URL path.'
		);

		$this->assertFalse(
			static::$sut->is_valid_path( 'file://absolute/filepath/directory/my-file.pdf' ),
			'Filepath will not validate is it does not belong to a whitelisted path (note that the tested path is relative).'
		);

		$this->assertFalse(
			static::$sut->is_valid_path( '/absolute/filepath/../../filepath/good.xml' ),
			'Filepaths containing directory traversals do not validate if the resolved path is no longer subordinate to a whitelisted path.'
		);

		$this->assertFalse(
			static::$sut->is_valid_path( '/absolute/disabled/filepath/guide.pdf' ),
			'Filepath will not validate if the corresponding rule has been disabled.'
		);
	}

	/**
	 * @testdox Ensure adding individual paths works as expected.
	 */
	public function test_adding_single_path() {
		$this->assertEquals(
			static::$sut->get_by_url( 'http://localhost:5000' )->get_id(),
			static::$sut->add_approved_directory( 'http://localhost:5000' ),
			'If the path was already added, adding it a second time (without trailing slash) will result in the existing row number (rule ID) being returned.'
		);

		$this->assertEquals(
			static::$sut->get_by_url( 'http://localhost:5000' )->get_id(),
			static::$sut->add_approved_directory( 'http://localhost:5000/' ),
			'If the path was already added, adding it a second time (with trailing slash) will result in the existing row number (rule ID) being returned.'
		);
	}

	/**
	 * @testdox Ensure individual approved directories can be enabled and disabled.
	 */
	public function test_enabling_disabling_individual_rules() {
		$approved_directory_rule = static::$sut->get_by_url( 'https://foo.bar/assets/' );
		$approved_directory_id   = $approved_directory_rule->get_id();
		$this->assertTrue( $approved_directory_rule->is_enabled() );

		static::$sut->disable_by_id( $approved_directory_id );
		$this->assertFalse( static::$sut->get_by_url( 'https://foo.bar/assets/' )->is_enabled() );

		static::$sut->enable_by_id( $approved_directory_id );
		$this->assertTrue( static::$sut->get_by_url( 'https://foo.bar/assets/' )->is_enabled() );
	}
}
