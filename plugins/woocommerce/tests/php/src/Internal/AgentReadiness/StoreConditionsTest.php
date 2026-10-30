<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\AgentReadiness;

use Automattic\WooCommerce\Internal\AgentReadiness\AgenticCommerceSettings;
use Automattic\WooCommerce\Internal\AgentReadiness\StoreConditions;
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
	 * Restore plain permalinks, which live on the global rewrite object outside the rollback.
	 */
	public function tearDown(): void {
		try {
			$this->set_permalink_structure( '' );
		} finally {
			parent::tearDown();
		}
	}

	/**
	 * @testdox With every required check passing, nothing blocks the switch.
	 */
	public function test_no_failing_required_checks_on_a_ready_store(): void {
		self::make_store_ready( $this );

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
		self::make_store_ready( $this );
		update_option( 'woocommerce_coming_soon', 'yes' );

		$this->assertSame( array( 'launched' ), $this->sut->failing_required() );
	}

	/**
	 * @testdox A draft or trashed policy page fails its check like an unset one, as a recommendation.
	 *
	 * @testWith ["draft"]
	 *           ["trash"]
	 *
	 * @param string $post_status Status of the selected refund page.
	 */
	public function test_unpublished_policy_page_fails( string $post_status ): void {
		self::make_store_ready( $this );
		update_option(
			AgenticCommerceSettings::REFUND_RETURNS_PAGE_OPTION,
			self::factory()->post->create(
				array(
					'post_type'   => 'page',
					'post_status' => $post_status,
				)
			)
		);

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertFalse( $checks['refund_returns_page']['ok'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['refund_returns_page']['level'] );
		$this->assertSame( array(), $this->sut->failing_required(), 'A missing policy page does not block the switch' );
	}

	/**
	 * @testdox Plain permalinks are required; guest checkout is only recommended.
	 */
	public function test_store_conditions_checks(): void {
		self::make_store_ready( $this );
		$this->set_permalink_structure( '' );
		update_option( 'woocommerce_enable_guest_checkout', 'no' );

		$checks = array_column( $this->sut->all(), null, 'id' );

		$this->assertSame( array( 'pretty_permalinks' ), $this->sut->failing_required() );
		$this->assertFalse( $checks['guest_checkout']['ok'] );
		$this->assertSame( StoreConditions::LEVEL_RECOMMENDED, $checks['guest_checkout']['level'] );
	}

	/**
	 * @testdox HTTPS is required.
	 */
	public function test_https_required(): void {
		self::make_store_ready( $this );
		remove_all_filters( 'pre_option_home' );
		update_option( 'home', 'http://example.org' );

		$this->assertSame( array( 'https' ), $this->sut->failing_required() );
	}

	/**
	 * Make every required check pass: store launched, HTTPS home URL and pretty permalinks.
	 *
	 * @param WC_Unit_Test_Case $test_case Test case, for its permalink helper.
	 */
	public static function make_store_ready( WC_Unit_Test_Case $test_case ): void {
		add_filter( 'pre_option_home', static fn() => 'https://example.org' );
		$test_case->set_permalink_structure( '/%postname%/' );
		update_option( 'woocommerce_coming_soon', 'no' );
	}
}
