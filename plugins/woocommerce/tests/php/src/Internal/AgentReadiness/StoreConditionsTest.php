<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
use Automattic\WooCommerce\Internal\Ucp\UcpDiscovery;
use WC_Unit_Test_Case;

/**
 * Tests for the store conditions' levels, which decide whether UCP can be turned on.
 */
class StoreConditionsTest extends WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var StoreConditions
	 */
	private $sut;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->sut = wc_get_container()->get( StoreConditions::class );
	}

	/**
	 * @testdox With every required check passing, nothing blocks the switch.
	 */
	public function test_no_failing_required_checks_on_a_ready_store(): void {
		self::make_store_ready();

		$this->assertSame( array(), $this->sut->failing_required() );
	}

	/**
	 * @testdox A required condition that couldn't be checked does not block the switch.
	 */
	public function test_couldnt_check_is_not_failing(): void {
		$conditions = array(
			array(
				'id'    => 'unknown',
				'ok'    => null,
				'level' => StoreConditions::LEVEL_REQUIRED,
			),
			array(
				'id'    => 'failing',
				'ok'    => false,
				'level' => StoreConditions::LEVEL_REQUIRED,
			),
		);

		$this->assertSame( array( 'failing' ), $this->sut->failing_required( $conditions ) );
	}

	/**
	 * @testdox Coming soon mode is required.
	 */
	public function test_coming_soon_required(): void {
		self::make_store_ready();
		update_option( 'woocommerce_coming_soon', 'yes' );

		$this->assertSame( array( 'launched' ), $this->sut->failing_required() );
	}

	/**
	 * @testdox A draft or trashed terms page fails its check like an unset one. Policy pages are recommended, so they don't block UCP.
	 *
	 * @testWith ["draft"]
	 *           ["trash"]
	 *
	 * @param string $post_status Status of the selected terms page.
	 */
	public function test_unpublished_policy_page_fails( string $post_status ): void {
		self::make_store_ready();
		update_option(
			AgenticCommerceSettings::TERMS_PAGE_OPTION,
			self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => $post_status,
				)
			)
		);

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertFalse( $checks['terms_page']['ok'] );
		$this->assertSame( array(), $this->sut->failing_required() );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['privacy_page']['level'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['terms_page']['level'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['refund_returns_page']['level'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['shipping_policy_page']['level'] );
	}

	/**
	 * @testdox A store page chosen as the terms page under Advanced > Page setup fails the terms check.
	 *
	 * @testWith ["cart"]
	 *           ["shop"]
	 *           ["myaccount"]
	 *
	 * @param string $store_page Store page used as the terms page.
	 */
	public function test_store_page_fails_terms_check( string $store_page ): void {
		self::make_store_ready();
		$page = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
			)
		);
		update_option( 'woocommerce_' . $store_page . '_page_id', $page );
		update_option( AgenticCommerceSettings::TERMS_PAGE_OPTION, $page );

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertFalse( $checks['terms_page']['ok'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['terms_page']['level'] );
		$this->assertSame( array(), $this->sut->failing_required() );
	}

	/**
	 * @testdox Guest checkout off is Optional, so it doesn't block the switch.
	 */
	public function test_store_conditions_checks(): void {
		self::make_store_ready();
		update_option( 'woocommerce_enable_guest_checkout', 'no' );

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertSame( array(), $this->sut->failing_required() );
		$this->assertFalse( $checks['guest_checkout']['ok'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['guest_checkout']['level'] );
		$this->assertFalse( StoreConditions::shows_required( $checks['guest_checkout'] ), 'AI assistants can still build carts without guest checkout' );
	}

	/**
	 * @testdox The UCP and policy page fixes link to AI shopping's settings, the policy ones to their field.
	 */
	public function test_fix_links_open_ai_shopping(): void {
		$checks   = array_column( $this->sut->all(), 'fix_url', 'id' );
		$shopping = admin_url( 'admin.php?page=wc-settings&tab=agentic-commerce&section=shopping' );

		$this->assertSame( $shopping, $checks['ucp_on'] );
		$this->assertSame( $shopping . '#' . AgenticCommerceSettings::TERMS_PAGE_OPTION, $checks['terms_page'] );
	}

	/**
	 * @testdox Either confirmation email is enough. Both off fails the check.
	 */
	public function test_order_email_passes_when_either_confirmation_is_on(): void {
		$emails     = WC()->mailer()->get_emails();
		$processing = $emails['WC_Email_Customer_Processing_Order'];
		$completed  = $emails['WC_Email_Customer_Completed_Order'];
		$previous   = array( $processing->enabled, $completed->enabled );
		$ok         = fn() => array_column( $this->sut->all(), 'ok', 'id' )['order_emails'];
		try {
			$processing->enabled = 'no';
			$completed->enabled  = 'no';
			$this->assertFalse( $ok() );

			$completed->enabled = 'yes';
			$this->assertTrue( $ok(), 'The Completed order email confirms a virtual order' );

			$completed->enabled  = 'no';
			$processing->enabled = 'yes';
			$this->assertTrue( $ok() );
		} finally {
			$processing->enabled = $previous[0];
			$completed->enabled  = $previous[1];
		}
	}

	/**
	 * @testdox HTTPS is required.
	 */
	public function test_https_required(): void {
		self::make_store_ready();
		remove_all_filters( 'pre_option_home' );
		update_option( 'home', 'http://example.org' );

		$this->assertSame( array( 'https' ), $this->sut->failing_required() );
	}

	/**
	 * @testdox Plain permalinks pass when WooCommerce can write the discovery file, and fail with both fixes when it can't. On multisite they always fail.
	 */
	public function test_plain_permalinks_depend_on_writing_the_discovery_file(): void {
		global $wp_rewrite;
		self::make_store_ready();
		$wp_rewrite->set_permalink_structure( '' );

		if ( is_multisite() ) {
			// Multisite never writes the file, so plain permalinks fail with only the permalink fix.
			$this->assertSame( array( 'ucp_discovery' ), $this->sut->failing_required() );
			$this->assertStringContainsString( 'except Plain', $this->ucp_discovery_fix() );
			$this->assertStringNotContainsString( 'writable', $this->ucp_discovery_fix() );
			return;
		}
		$this->assertNotContains( 'ucp_discovery', $this->sut->failing_required() );

		$locked = get_temp_dir() . uniqid( 'wc-ucp-locked-' );
		mkdir( $locked ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
		chmod( $locked, 0555 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $locked . '/ucp' );
		try {
			if ( is_writable( $locked ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
				$this->markTestSkipped( 'The test runs as a user who can write any directory (root).' );
			}
			$this->assertSame( array( 'ucp_discovery' ), $this->sut->failing_required() );
			$fix = $this->ucp_discovery_fix();
			$this->assertStringContainsString( 'except Plain', $fix );
			$this->assertStringContainsString( 'index.php', $fix );
			$this->assertStringContainsString( 'writable', $fix );
			$checks = array_column( $this->sut->all(), null, 'id' );
			$this->assertSame( admin_url( 'options-permalink.php' ), $checks['ucp_discovery']['fix_url'] );
			$this->assertSame( 'Use a readable permalink structure', $checks['ucp_discovery']['label'], 'Plain permalinks are the cause, so the step names them' );
		} finally {
			chmod( $locked, 0755 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			rmdir( $locked ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
		}
	}

	/**
	 * @testdox A /.well-known/ucp file in the way fails the UCP address check with the fix for its reason, and deleting it passes the check.
	 * @testWith ["foreign", "A file at "]
	 *           ["outdated", "The file at "]
	 *           ["not_json", "The file at "]
	 *           ["unreadable", "WooCommerce can’t read the file at "]
	 *
	 * @param string $file   Someone else's file, an old profile, invalid JSON, or a file the web server can't read.
	 * @param string $prefix How the fix should start.
	 */
	public function test_ucp_file_fix( string $file, string $prefix ): void {
		self::make_store_ready();
		$path = get_temp_dir() . uniqid( 'wc-ucp-' ) . '/.well-known/ucp';
		add_filter( 'woocommerce_ucp_discovery_file_path', fn() => $path );

		$profile                   = ( new UcpDiscovery() )->get_profile();
		$profile['ucp']['version'] = '1999-01-01';
		$encoded                   = (string) wp_json_encode( $profile );
		$contents                  = array(
			'foreign'    => '{}',
			'outdated'   => $encoded,
			'not_json'   => '{bad',
			'unreadable' => $encoded,
		);

		wp_mkdir_p( dirname( $path ) );
		file_put_contents( $path, $contents[ $file ] ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
		if ( 'unreadable' === $file ) {
			chmod( $path, 0000 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
		}

		try {
			if ( 'unreadable' === $file && is_readable( $path ) ) {
				$this->markTestSkipped( 'The test runs as a user who can read any file (root).' );
			}
			$this->assertSame( array( 'ucp_discovery' ), $this->sut->failing_required() );
			$this->assertStringStartsWith( $prefix, $this->ucp_discovery_fix() );
			$this->assertStringContainsString( $path, $this->ucp_discovery_fix() );
			$checks = array_column( $this->sut->all(), null, 'id' );
			$this->assertSame( '', $checks['ucp_discovery']['fix_url'] );
			$this->assertSame( 'Fix your connection URL', $checks['ucp_discovery']['label'], 'A file is the cause, not permalinks' );

			// Following the fix passes the check: with the file gone and pretty permalinks, nothing is in the way.
			if ( 'unreadable' === $file ) {
				chmod( $path, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			}
			wp_delete_file( $path );
			$this->assertSame( array(), $this->sut->failing_required() );
		} finally {
			if ( is_file( $path ) ) {
				chmod( $path, 0644 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			}
			self::remove_ucp_file( $path );
		}
	}

	/**
	 * The fix text the UCP address check shows now.
	 *
	 * @return string
	 */
	private function ucp_discovery_fix(): string {
		$checks = array_column( $this->sut->all(), null, 'id' );
		return $checks['ucp_discovery']['fix'];
	}

	/**
	 * Make every required check pass: the store is launched, the home URL is HTTPS and permalinks aren't Plain.
	 * Privacy, terms and the refund page are set so those checks count as done.
	 */
	public static function make_store_ready(): void {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		add_filter( 'pre_option_home', static fn() => 'https://example.org' );
		update_option( 'woocommerce_coming_soon', 'no' );
		$page = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'publish',
				'post_title'  => 'Policy',
			)
		);
		foreach ( array( AgenticCommerceSettings::PRIVACY_PAGE_OPTION, AgenticCommerceSettings::TERMS_PAGE_OPTION, AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION ) as $option ) {
			update_option( $option, $page );
		}
	}

	/**
	 * Delete a test discovery file and the two folders created for it.
	 *
	 * @param string $path File path under a `wc-ucp-*` temp folder.
	 */
	private static function remove_ucp_file( string $path ): void {
		wp_delete_file( $path );
		foreach ( array( dirname( $path ), dirname( $path, 2 ) ) as $dir ) {
			if ( is_dir( $dir ) ) {
				rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir
			}
		}
	}
}
