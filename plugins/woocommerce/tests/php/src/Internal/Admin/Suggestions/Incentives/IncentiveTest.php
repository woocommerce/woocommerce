<?php
declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Admin\Suggestions\Incentives;

use Automattic\WooCommerce\Internal\Admin\Suggestions\Incentives\Incentive;
use WC_Unit_Test_Case;

/**
 * Incentive provider test.
 *
 * @class Incentive
 */
class IncentiveTest extends WC_Unit_Test_Case {
	/**
	 * System under test.
	 *
	 * @var Incentive
	 */
	protected $sut;

	/**
	 * The incentive's suggestion ID.
	 *
	 * @var string
	 */
	protected string $suggestion_id;

	/**
	 * The ID of the store admin user.
	 *
	 * @var int
	 */
	protected $store_admin_id;

	/**
	 * Set up test.
	 */
	public function setUp(): void {
		parent::setUp();

		$this->store_admin_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->store_admin_id );

		$this->suggestion_id = 'suggestion1';

		$this->sut = $this->getMockForAbstractClass( Incentive::class, array( $this->suggestion_id ) );
	}

	/**
	 * Test getting all incentives.
	 */
	public function test_get_all() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'        => 'incentive1',
						'promo_id'  => 'promo1',
						'type'      => 'type1',
						'something' => 'else', // Extra data that should be ignored.
					),
					array(), // Invalid empty incentive.
					array(
						'something' => 'else', // Invalid incentive that is missing all required entries.
					),
					array(
						'id' => 'id', // Invalid incentive that is missing promo ID and type.
					),
					array(
						'type' => 'type', // Invalid incentive that is missing ID and promo ID.
					),
					array(
						'promo_id' => 'promo1', // Invalid incentive that is missing ID and type.
					),
					array(
						'id'        => 'incentive2',
						'promo_id'  => 'promo2',
						'type'      => 'type2',
						'something' => 'else', // Extra data that should be ignored.
					),
					array(
						'id'       => 'id', // Invalid incentive that is missing type.
						'promo_id' => 'promo1',
					),
					array(
						'id'        => 'id2',
						'type'      => 'type', // Invalid incentive that is missing promo ID.
						'something' => 'else', // Extra data that should be ignored.
					),
					array(
						'id'        => 'id2',
						'promo_id'  => 'promo2', // Invalid incentive that is missing type.
						'something' => 'else', // Extra data that should be ignored.
					),
				)
			);

		// Act.
		$result = $this->sut->get_all( 'US' );

		// Assert.
		$this->assertCount( 2, $result );
		$this->assertSame( 'incentive1', $result[0]['id'] );
		$this->assertSame( 'incentive2', $result[1]['id'] );
	}

	/**
	 * Test getting all incentives with a specific type.
	 */
	public function test_get_all_with_incentive_type() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
					array(
						'id'       => 'incentive3',
						'promo_id' => 'promo3',
						'type'     => 'type1',
					),
				)
			);

		// Act.
		$result = $this->sut->get_all( 'US', 'type1' );

		// Assert.
		$this->assertCount( 2, $result );
		$this->assertSame( 'incentive1', $result[0]['id'] );
		$this->assertSame( 'incentive3', $result[1]['id'] );
	}

	/**
	 * Test getting an incentive by promo ID.
	 */
	public function test_get_by_promo_id() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
					array(
						'id'       => 'incentive3',
						'promo_id' => 'promo3',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive4',
						'promo_id' => 'promo4',
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_promo_id( 'promo2', 'US' );

		// Assert.
		$this->assertSame( 'incentive2', $result['id'] );
		$this->assertSame( 'promo2', $result['promo_id'] );
		$this->assertSame( 'type2', $result['type'] );
	}

	/**
	 * Test getting an incentive by promo ID with a specific type.
	 */
	public function test_get_by_promo_id_with_incentive_type() {
		// Arrange.
		$this->sut
			->expects( $this->exactly( 2 ) )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive3',
						'promo_id' => 'promo3', // Not the promo ID we're looking for.
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive1_1',
						'promo_id' => 'promo1', // Same promo ID, different type.
						'type'     => 'type2',
					),
					array(
						'id'       => 'incentive4',
						'promo_id' => 'promo4',
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_promo_id( 'promo1', 'US', 'type1' );

		// Assert.
		$this->assertSame( 'incentive1', $result['id'] );
		$this->assertSame( 'promo1', $result['promo_id'] );
		$this->assertSame( 'type1', $result['type'] );

		// Act.
		$result = $this->sut->get_by_promo_id( 'promo1', 'US', 'type2' );

		// Assert.
		$this->assertSame( 'incentive1_1', $result['id'] );
		$this->assertSame( 'promo1', $result['promo_id'] );
		$this->assertSame( 'type2', $result['type'] );
	}

	/**
	 * Test getting an incentive by promo ID when the ID is invalid.
	 */
	public function test_get_by_promo_id_with_invalid_id() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_promo_id( 'bogus_id', 'US' );

		// Assert.
		$this->assertNull( $result );
	}

	/**
	 * Test getting an incentive by ID when the incentive is invalid.
	 */
	public function test_get_by_promo_id_with_invalid_incentive() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1', // No type.
					),
					array(
						'promo_id' => 'promo1', // No ID.
						'type'     => 'type1',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_promo_id( 'promo1', 'US' );

		// Assert.
		$this->assertNull( $result );
	}

	/**
	 * Test getting an incentive by promo ID when there are multiple incentives with the same promo ID.
	 */
	public function test_get_by_promo_id_returns_first() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo1', // Same promo ID.
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_promo_id( 'promo1', 'US' );

		// Assert.
		$this->assertSame( 'incentive1', $result['id'] );
		$this->assertSame( 'promo1', $result['promo_id'] );
		$this->assertSame( 'type1', $result['type'] );
	}

	/**
	 * Test getting an incentive by ID.
	 */
	public function test_get_by_id() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
					array(
						'id'       => 'incentive3',
						'promo_id' => 'promo3',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive4',
						'promo_id' => 'promo4',
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_id( 'incentive2', 'US' );

		// Assert.
		$this->assertSame( 'incentive2', $result['id'] );
		$this->assertSame( 'type2', $result['type'] );
	}

	/**
	 * Test getting an incentive by ID when the ID is invalid.
	 */
	public function test_get_by_id_with_invalid_id() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'bogus_id', // This is not the incentive ID.
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive2',
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_id( 'bogus_id', 'US' );

		// Assert.
		$this->assertNull( $result );
	}

	/**
	 * Test getting an incentive by ID when the incentive is invalid.
	 */
	public function test_get_by_id_with_invalid_incentive() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1', // No type.
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_id( 'incentive1', 'US' );

		// Assert.
		$this->assertNull( $result );
	}

	/**
	 * Test getting an incentive by ID when there are multiple incentives with the same ID.
	 */
	public function test_get_by_id_returns_first() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'get_incentives' )
			->with( 'US' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
					array(
						'id'       => 'incentive1', // Same ID.
						'promo_id' => 'promo2',
						'type'     => 'type2',
					),
				)
			);

		// Act.
		$result = $this->sut->get_by_id( 'incentive1', 'US' );

		// Assert.
		$this->assertSame( 'incentive1', $result['id'] );
		$this->assertSame( 'promo1', $result['promo_id'] );
		$this->assertSame( 'type1', $result['type'] );
	}

	/**
	 * Test is_visible when the extension is active.
	 */
	public function test_is_visible_extension_active() {
		// Arrange.
		$this->sut
			->expects( $this->once() )
			->method( 'is_extension_active' )
			->willReturn( true );

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'RO' );

		// Assert.
		$this->assertFalse( $result );
	}

	/**
	 * Test is_visible when instructed to skip the extension active check.
	 */
	public function test_is_visible_skips_extension_active_check() {
		// Arrange.
		$this->sut
			->expects( $this->never() )
			->method( 'is_extension_active' );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => true );
		add_filter( 'user_has_cap', $filter_callback );

		$this->sut
			->expects( $this->any() )
			->method( 'get_incentives' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'RO', true );

		// Assert.
		$this->assertTrue( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test is_visible when the user does not have the required capabilities.
	 */
	public function test_is_visible_user_does_not_have_caps() {
		// Arrange.
		$this->sut
			->expects( $this->any() )
			->method( 'is_extension_active' )
			->willReturn( false );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => false );
		add_filter( 'user_has_cap', $filter_callback );

		$this->sut
			->expects( $this->any() )
			->method( 'get_incentives' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'RO' );

		// Assert.
		$this->assertFalse( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test is_visible when there are no incentives with the given ID.
	 */
	public function test_is_visible_no_incentive() {
		// Arrange.
		$this->sut
			->expects( $this->any() )
			->method( 'is_extension_active' )
			->willReturn( false );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => true );
		add_filter( 'user_has_cap', $filter_callback );

		$this->sut
			->expects( $this->any() )
			->method( 'get_incentives' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		// Act.
		$result = $this->sut->is_visible( 'bogus_id', 'RO' );

		// Assert.
		$this->assertFalse( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
	}

	/**
	 * Test is_visible when the incentive is dismissed for all contexts.
	 */
	public function test_is_visible_dismissed_for_all_contexts() {
		// Arrange.
		$this->sut
			->expects( $this->any() )
			->method( 'is_extension_active' )
			->willReturn( false );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => true );
		add_filter( 'user_has_cap', $filter_callback );

		$this->sut
			->expects( $this->any() )
			->method( 'get_incentives' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'incentive1',
						'context'   => 'all', // Dismissed for all contexts.
						'timestamp' => time(),
					),
				),
			)
		);

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'RO' );

		// Assert.
		$this->assertFalse( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test is_visible when the incentive is dismissed for a certain context.
	 */
	public function test_is_visible_dismissed_within_context() {
		// Arrange.
		$this->sut
			->expects( $this->any() )
			->method( 'is_extension_active' )
			->willReturn( false );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => true );
		add_filter( 'user_has_cap', $filter_callback );

		$this->sut
			->expects( $this->any() )
			->method( 'get_incentives' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'incentive1',
						'context'   => 'context1',
						'timestamp' => time(),
					),
				),
			)
		);

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'RO' );

		// Assert.
		$this->assertTrue( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test is_visible.
	 */
	public function test_is_visible() {
		// Arrange.
		$this->sut
			->expects( $this->any() )
			->method( 'is_extension_active' )
			->willReturn( false );

		// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		$filter_callback = fn( $caps ) => array( 'manage_woocommerce' => true );
		add_filter( 'user_has_cap', $filter_callback );

		$this->sut
			->expects( $this->any() )
			->method( 'get_incentives' )
			->with( 'RO' )
			->willReturn(
				array(
					array(
						'id'       => 'incentive1',
						'promo_id' => 'promo1',
						'type'     => 'type1',
					),
				)
			);

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'incentive2', // Another one is dismissed.
						'context'   => 'context1',
						'timestamp' => time(),
					),
				),
				'suggestion2'        => array(
					array(
						'id'        => 'incentive1', // Dismissed for another suggestion.
						'context'   => 'context2',
						'timestamp' => time(),
					),
				),
			)
		);

		// Act.
		$result = $this->sut->is_visible( 'incentive1', 'RO' );

		// Assert.
		$this->assertTrue( $result );

		// Clean up.
		remove_filter( 'user_has_cap', $filter_callback );
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test dismiss when there are no dismissals.
	 */
	public function test_dismiss_with_no_dismissals() {
		// Arrange.
		$incentive_id = 'incentive1';
		$context      = 'context1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				// No dismissals for the current suggestion.
				'suggestion2' => array(
					array(
						'id'        => $incentive_id, // Dismissed for another suggestion.
						'context'   => 'context2',
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$result = $this->sut->dismiss( $incentive_id, $context, 456 );

		// Assert.
		$this->assertTrue( $result );
		$this->assertSame(
			array(
				$this->suggestion_id => array( // This comes first because of the sorting by key.
					array(
						'id'        => $incentive_id,
						'context'   => $context,
						'timestamp' => 456,
					),
				),
				'suggestion2'        => array(
					array(
						'id'        => $incentive_id,
						'context'   => 'context2',
						'timestamp' => 1234,
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test dismiss when there are dismissals.
	 */
	public function test_dismiss_with_other_dismissals() {
		// Arrange.
		$incentive_id = 'incentive1';
		$context      = 'context1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'incentive2', // Another one is dismissed.
						'context'   => $context,
						'timestamp' => 1234,
					),
				),
				'suggestion2'        => array(
					array(
						'id'        => $incentive_id, // Dismissed for another suggestion.
						'context'   => 'context2',
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$result = $this->sut->dismiss( $incentive_id, $context, 456 );

		// Assert.
		$this->assertTrue( $result );
		$this->assertSame(
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'incentive2',
						'context'   => $context,
						'timestamp' => 1234,
					),
					array(
						'id'        => $incentive_id,
						'context'   => $context,
						'timestamp' => 456,
					),
				),
				'suggestion2'        => array(
					array(
						'id'        => $incentive_id,
						'context'   => 'context2',
						'timestamp' => 1234,
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test dismiss when the incentive is already dismissed.
	 */
	public function test_dismiss_already_dismissed() {
		// Arrange.
		$incentive_id = 'incentive1';
		$context      = 'context1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id, // Already dismissed for all contexts.
						'context'   => 'all',
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$result = $this->sut->dismiss( $incentive_id );

		// Assert.
		$this->assertFalse( $result );
		$this->assertSame(
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id,
						'context'   => 'all',
						'timestamp' => 1234, // Remains the same.
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test dismiss when the incentive is already dismissed within the context.
	 */
	public function test_dismiss_already_dismissed_within_context() {
		// Arrange.
		$incentive_id = 'incentive1';
		$context      = 'context1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id, // Already dismissed within the context.
						'context'   => $context,
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$this->assertTrue( $this->sut->dismiss( $incentive_id, 'all', 456 ) );

		$this->assertSame(
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id,
						'context'   => $context,
						'timestamp' => 1234, // Remains the same.
					),
					array(
						'id'        => $incentive_id,
						'context'   => 'all',
						'timestamp' => 456,
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Act.
		$this->assertFalse( $this->sut->dismiss( $incentive_id, 'another_context' ) );

		// Since it was already dismissed for all contexts, it should not be dismissed for another context.
		$this->assertSame(
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id,
						'context'   => $context,
						'timestamp' => 1234,
					),
					array(
						'id'        => $incentive_id,
						'context'   => 'all',
						'timestamp' => 456,
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test dismiss in a certain context when the incentive is already dismissed within the context.
	 */
	public function test_dismiss_in_context_already_dismissed_within_context() {
		// Arrange.
		$incentive_id = 'incentive1';
		$context      = 'context1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id, // Already dismissed.
						'context'   => $context,
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$this->assertFalse( $this->sut->dismiss( $incentive_id, $context, 456 ) );

		$this->assertSame(
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id,
						'context'   => $context,
						'timestamp' => 1234, // Remains the same.
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Act.
		$this->assertTrue( $this->sut->dismiss( $incentive_id, 'another_context', 789 ) );

		$this->assertSame(
			array(
				$this->suggestion_id => array(
					array(
						'id'        => $incentive_id,
						'context'   => $context,
						'timestamp' => 1234,
					),
					array(
						'id'        => $incentive_id,
						'context'   => 'another_context',
						'timestamp' => 789,
					),
				),
			),
			get_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed', true )
		);

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test is_dismissed.
	 */
	public function test_is_dismissed() {
		// Arrange.
		$incentive_id = 'incentive1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'another',
						'context'   => 'context1',
						'timestamp' => 12345,
					),
					array(
						'id'        => $incentive_id,
						'context'   => 'all', // Dismissed for all contexts.
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$result = $this->sut->is_dismissed( $incentive_id );

		// Assert.
		$this->assertTrue( $result );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test is_dismissed when the incentive is dismissed within a context.
	 */
	public function test_is_dismissed_within_context() {
		// Arrange.
		$incentive_id = 'incentive1';
		$context      = 'context1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'another',
						'context'   => 'context1',
						'timestamp' => 12345,
					),
					array(
						'id'        => $incentive_id,
						'context'   => $context, // Only dismissed for this context, not for all.
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$this->assertTrue( $this->sut->is_dismissed( $incentive_id, $context ) );
		$this->assertFalse( $this->sut->is_dismissed( $incentive_id ) ); // The incentive is not dismissed for all contexts.
		$this->assertFalse( $this->sut->is_dismissed( $incentive_id, 'bogus_context' ) );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test is_dismissed when the incentive is dismissed for all contexts.
	 */
	public function test_is_dismissed_for_all_contexts() {
		// Arrange.
		$incentive_id = 'incentive1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'another',
						'context'   => 'context1',
						'timestamp' => 12345,
					),
					array(
						'id'        => $incentive_id,
						'context'   => 'all', // Dismissed for all contexts.
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$this->assertTrue( $this->sut->is_dismissed( $incentive_id ) );
		$this->assertTrue( $this->sut->is_dismissed( $incentive_id, 'some_context' ) );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}

	/**
	 * Test is_dismissed when the incentive is dismissed for another suggestion.
	 */
	public function test_is_dismissed_for_another_suggestion() {
		// Arrange.
		$incentive_id = 'incentive1';

		update_user_meta(
			$this->store_admin_id,
			Incentive::PREFIX . 'dismissed',
			array(
				$this->suggestion_id => array(
					array(
						'id'        => 'another',
						'context'   => 'context1',
						'timestamp' => 12345,
					),
				),
				'suggestion2'        => array(
					array(
						'id'        => $incentive_id,
						'context'   => 'context2',
						'timestamp' => 1234,
					),
				),
			)
		);

		// Act.
		$result = $this->sut->is_dismissed( $incentive_id );

		// Assert.
		$this->assertFalse( $result );

		// Clean up.
		delete_user_meta( $this->store_admin_id, Incentive::PREFIX . 'dismissed' );
	}
}
