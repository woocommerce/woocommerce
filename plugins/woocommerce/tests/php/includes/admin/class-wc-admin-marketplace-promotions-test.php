<?php
declare( strict_types = 1 );

/**
 * Tests for WC_Admin_Marketplace_Promotions rule-based promo cards.
 *
 * The transport and rule engine are plugin-agnostic; the pilot payload (WCCOM-2634)
 * targets Product Add-Ons. Production thresholds are order_count >= 100 and
 * product_count >= 20 (see the wccom_iam_promos payload). These tests assert the rule
 * *shape* resolves correctly using clean-environment thresholds, plus the gating
 * behaviour in both directions, so they do not depend on seeded order/product volume.
 *
 * @package WooCommerce\Tests\Admin
 */
class WC_Admin_Marketplace_Promotions_Test extends WC_Unit_Test_Case {

	/**
	 * Add-on plugin directory slugs excluded by the pilot rule (official + competitors).
	 *
	 * plugins_activated matches the plugin directory slug, not the plugin file path.
	 *
	 * @var string[]
	 */
	private const ADD_ON_SLUGS = array(
		'woocommerce-product-addons',
		'advanced-product-fields-for-woocommerce',
		'woo-custom-product-addons',
		'woo-custom-product-addons-pro',
		'woo-extra-product-options',
		'woocommerce-tm-extra-product-options',
		'yith-woocommerce-product-add-ons',
		'woocommerce-product-addon',
	);

	/**
	 * Callback registered on option_active_plugins by tests that need a plugin to read as active.
	 *
	 * @var callable|null
	 */
	private $active_plugins_filter = null;

	/**
	 * Clean up state between tests.
	 */
	public function tearDown(): void {
		delete_transient( WC_Admin_Marketplace_Promotions::TRANSIENT_NAME );
		delete_option( 'woocommerce_allow_tracking' );
		if ( null !== $this->active_plugins_filter ) {
			remove_filter( 'option_active_plugins', $this->active_plugins_filter );
			$this->active_plugins_filter = null;
		}
		$this->reset_orders_promo_cache();
		wp_set_current_user( 0 );

		parent::tearDown();
	}

	/**
	 * Clear the request-scoped Orders promo card cache between tests.
	 */
	private function reset_orders_promo_cache(): void {
		$resolved = new ReflectionProperty( WC_Admin_Marketplace_Promotions::class, 'orders_promo_card_resolved' );
		$resolved->setAccessible( true );
		$resolved->setValue( null, false );

		$card = new ReflectionProperty( WC_Admin_Marketplace_Promotions::class, 'orders_promo_card' );
		$card->setAccessible( true );
		$card->setValue( null, null );
	}

	/**
	 * Build the "none of these add-on plugins active" exclusion rule.
	 *
	 * @param string[] $extra_slugs Extra directory slugs to add to the exclusion list.
	 * @return array
	 */
	private function add_on_exclusion_rule( array $extra_slugs = array() ): array {
		$operands = array_map(
			static function ( string $slug ): array {
				return array(
					'type'    => 'plugins_activated',
					'plugins' => array( $slug ),
				);
			},
			array_merge( self::ADD_ON_SLUGS, $extra_slugs )
		);

		return array(
			'type'    => 'not',
			'operand' => array(
				'type'     => 'or',
				'operands' => $operands,
			),
		);
	}

	/**
	 * Store a single rule-based Product Add-Ons promo in the promotions transient.
	 *
	 * @param array $local_rules The local_rules to attach.
	 * @param array $pages       The pages the promo targets.
	 */
	private function set_rule_based_promo( array $local_rules, array $pages = array(
		array(
			'page' => 'wc-admin',
			'path' => '/',
		),
	) ): void {
		set_transient(
			WC_Admin_Marketplace_Promotions::TRANSIENT_NAME,
			array(
				array(
					'id'            => 'product-add-ons-orders',
					'date_from_gmt' => '2025-01-01 00:00:00',
					'date_to_gmt'   => '2099-01-01 00:00:00',
					'format'        => WC_Admin_Marketplace_Promotions::RULE_BASED_FORMAT,
					'pages'         => $pages,
					'title'         => array( 'en_US' => 'Add options and personalization to your products' ),
					'content'       => array( 'en_US' => 'Let customers add gift wrapping, custom text, file uploads, or paid options right on the product page.' ),
					'cta_label'     => array( 'en_US' => 'See Product Add-Ons' ),
					'cta_link'      => 'https://woocommerce.com/products/product-add-ons/',
					'local_rules'   => $local_rules,
				),
			)
		);
	}

	/**
	 * The pilot rule set, with order/product thresholds overridable so the engine can be
	 * exercised without seeding production-scale volume.
	 *
	 * @param int $order_threshold   order_count >= value.
	 * @param int $product_threshold product_count >= value.
	 * @return array
	 */
	private function pilot_rules( int $order_threshold = 0, int $product_threshold = 0 ): array {
		return array(
			array(
				'type'        => 'option',
				'option_name' => 'woocommerce_allow_tracking',
				'operation'   => '=',
				'value'       => 'yes',
			),
			array(
				'type'      => 'order_count',
				'operation' => '>=',
				'value'     => $order_threshold,
			),
			array(
				'type'      => 'product_count',
				'operation' => '>=',
				'value'     => $product_threshold,
			),
			$this->add_on_exclusion_rule(),
		);
	}

	/**
	 * @testdox Eligible Product Add-Ons promo is converted into a promo card and local_rules stripped.
	 */
	public function test_eligible_rule_based_promo_is_converted_to_promo_card(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$this->set_rule_based_promo( $this->pilot_rules() );

		$promotions = WC_Admin_Marketplace_Promotions::get_active_promotions();

		$this->assertCount( 1, $promotions );
		$this->assertSame( 'promo-card', $promotions[0]['format'] );
		$this->assertArrayNotHasKey( 'local_rules', $promotions[0] );
		$this->assertSame( 'See Product Add-Ons', $promotions[0]['cta_label']['en_US'] );
	}

	/**
	 * @testdox Promo is suppressed when the store already has an add-on plugin active.
	 */
	public function test_active_add_on_plugin_suppresses_promo(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Force WooCommerce itself to read as an active plugin, then include its slug in the
		// exclusion list. This proves not[ or[ plugins_activated... ] ] suppresses when ANY
		// listed plugin is active (the encoding that a single plugins_activated list would get wrong).
		// The callback is stored so tearDown removes only it, not unrelated filters on the hook.
		$this->active_plugins_filter = static function (): array {
			return array( 'woocommerce/woocommerce.php' );
		};
		add_filter( 'option_active_plugins', $this->active_plugins_filter );

		$rules    = $this->pilot_rules();
		$rules[3] = $this->add_on_exclusion_rule( array( 'woocommerce' ) );
		$this->set_rule_based_promo( $rules );

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox Promo is suppressed when product_count is below the threshold.
	 */
	public function test_product_count_below_threshold_suppresses_promo(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Clean test store has fewer than 9999 published products.
		$this->set_rule_based_promo( $this->pilot_rules( 0, 9999 ) );

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox Promo is suppressed when tracking is not opted in.
	 */
	public function test_tracking_opt_out_suppresses_promo(): void {
		delete_option( 'woocommerce_allow_tracking' );

		$this->set_rule_based_promo( $this->pilot_rules() );

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox Rule-based promotions are suppressed when local rules explicitly fail.
	 */
	public function test_rule_based_promotions_are_suppressed_when_rules_fail(): void {
		$this->set_rule_based_promo( array( array( 'type' => 'fail' ) ) );

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox Malformed rule-based promotions fail closed.
	 */
	public function test_malformed_rule_based_promotions_fail_closed(): void {
		// order_count with no value/operation is invalid and must not pass.
		$this->set_rule_based_promo( array( array( 'type' => 'order_count' ) ) );

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox A `not` rule with an empty operand fails closed (does not flip to true).
	 */
	public function test_not_rule_with_empty_operand_fails_closed(): void {
		// not( [] ) would evaluate to true (RuleEvaluator returns false for an empty rule set,
		// which `not` flips), so the validator must reject the empty operand first.
		$this->set_rule_based_promo(
			array(
				array(
					'type'    => 'not',
					'operand' => array(),
				),
			)
		);

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox A `not` rule wrapping an unknown rule type fails closed.
	 */
	public function test_not_rule_with_unknown_nested_type_fails_closed(): void {
		// An unknown type resolves to the fail processor; without rejecting it, `not` would
		// flip its failure into a pass.
		$this->set_rule_based_promo(
			array(
				array(
					'type'    => 'not',
					'operand' => array( 'type' => 'totally_unknown_rule_type' ),
				),
			)
		);

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox A `not` rule wrapping an empty `or` fails closed.
	 */
	public function test_not_rule_with_empty_nested_or_fails_closed(): void {
		$this->set_rule_based_promo(
			array(
				array(
					'type'    => 'not',
					'operand' => array(
						'type'     => 'or',
						'operands' => array(),
					),
				),
			)
		);

		$this->assertSame( array(), WC_Admin_Marketplace_Promotions::get_active_promotions() );
	}

	/**
	 * @testdox An OR rule whose operands are AND groups (arrays of rules) is accepted.
	 */
	public function test_or_rule_with_and_group_operands_is_accepted(): void {
		// RemoteSpecs allows each OR operand to be an AND group (array of rules), not only a
		// single rule. The validator must accept that shape so RuleEvaluator can evaluate it.
		$this->set_rule_based_promo(
			array(
				array(
					'type'     => 'or',
					// Each operand is an AND group (array of rules); the first passes, so OR passes.
					'operands' => array(
						array( array( 'type' => 'pass' ) ),
						array( array( 'type' => 'fail' ) ),
					),
				),
			)
		);

		$promotions = WC_Admin_Marketplace_Promotions::get_active_promotions();

		$this->assertCount( 1, $promotions );
		$this->assertSame( 'promo-card', $promotions[0]['format'] );
	}

	/**
	 * @testdox An eligible promo targeting the Orders screen is returned with the order count.
	 */
	public function test_orders_promo_card_returned_when_targeting_orders(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$this->set_rule_based_promo( $this->pilot_rules(), array( array( 'page' => 'wc-orders' ) ) );

		$card = WC_Admin_Marketplace_Promotions::get_orders_promo_card();

		$this->assertIsArray( $card );
		$this->assertSame( 'product-add-ons-orders', $card['id'] );
		$this->assertSame( 'promo-card', $card['promotion']['format'] );
		$this->assertSame( 'See Product Add-Ons', $card['promotion']['cta_label']['en_US'] );
		$this->assertArrayHasKey( 'order_count', $card );
		$this->assertIsInt( $card['order_count'] );
	}

	/**
	 * @testdox A promo that targets only the Marketplace is not shown on the Orders screen.
	 */
	public function test_orders_promo_card_null_when_not_targeting_orders(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		// Default pages target the Marketplace app (page=wc-admin), not the Orders list.
		$this->set_rule_based_promo( $this->pilot_rules() );

		$this->assertNull( WC_Admin_Marketplace_Promotions::get_orders_promo_card() );
	}

	/**
	 * @testdox No Orders promo card is returned when no promotion is active.
	 */
	public function test_orders_promo_card_null_when_no_promotions(): void {
		$this->assertNull( WC_Admin_Marketplace_Promotions::get_orders_promo_card() );
	}

	/**
	 * @testdox An Orders promo without an id is never shown (it could not be dismissed).
	 */
	public function test_orders_promo_card_requires_an_id(): void {
		set_transient(
			WC_Admin_Marketplace_Promotions::TRANSIENT_NAME,
			array(
				array(
					'date_from_gmt' => '2025-01-01 00:00:00',
					'date_to_gmt'   => '2099-01-01 00:00:00',
					'format'        => 'promo-card',
					'pages'         => array( array( 'page' => 'wc-orders' ) ),
					'title'         => array( 'en_US' => 'No id here' ),
				),
			)
		);

		$this->assertNull( WC_Admin_Marketplace_Promotions::get_orders_promo_card() );
	}

	/**
	 * @testdox A promo the current user has dismissed is not shown.
	 */
	public function test_orders_promo_card_skipped_when_dismissed(): void {
		update_option( 'woocommerce_allow_tracking', 'yes' );

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		update_user_meta( $user_id, WC_Admin_Marketplace_Promotions::DISMISSED_PROMOS_META, array( 'product-add-ons-orders' ) );

		$this->set_rule_based_promo( $this->pilot_rules(), array( array( 'page' => 'wc-orders' ) ) );

		$this->assertNull( WC_Admin_Marketplace_Promotions::get_orders_promo_card() );
	}

	/**
	 * @testdox The dismiss endpoint records the promo id once, per user.
	 */
	public function test_dismiss_request_persists_per_user(): void {
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );

		$request = new WP_REST_Request( 'POST', '/wc-admin/marketplace-promotions/dismiss' );
		$request->set_param( 'id', 'product-add-ons-orders' );

		$response = WC_Admin_Marketplace_Promotions::handle_dismiss_request( $request );
		$this->assertTrue( $response->get_data()['dismissed'] );

		$dismissed = get_user_meta( $user_id, WC_Admin_Marketplace_Promotions::DISMISSED_PROMOS_META, true );
		$this->assertSame( array( 'product-add-ons-orders' ), $dismissed );

		// Idempotent: dismissing again does not duplicate the id.
		WC_Admin_Marketplace_Promotions::handle_dismiss_request( $request );
		$this->assertCount( 1, get_user_meta( $user_id, WC_Admin_Marketplace_Promotions::DISMISSED_PROMOS_META, true ) );
	}
}
