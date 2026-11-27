<?php
/**
 * TaxRateAbilitiesTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests for the tax-rate-create, tax-rate-update and tax-rate-delete abilities.
 */
class TaxRateAbilitiesTest extends \WC_Unit_Test_Case {

	/**
	 * Administrator used as the current user.
	 *
	 * @var int
	 */
	private static $administrator_id;

	/**
	 * Init action count restored in tearDown.
	 *
	 * @var int|null
	 */
	private $original_init_count;

	/**
	 * Create the administrator once for the class.
	 *
	 * @param \WP_UnitTest_Factory $factory WordPress unit test factory.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		self::$administrator_id = $factory->user->create( array( 'role' => 'administrator' ) );
	}

	/**
	 * Start each test from an empty abilities registry and no tax rates.
	 */
	public function setUp(): void {
		global $wp_actions, $wpdb;

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
		}

		// The Abilities API registry refuses to initialize before `init` has fired.
		$this->original_init_count = $wp_actions['init'] ?? null;
		$wp_actions['init']        = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		parent::setUp();
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rate_locations" );
		$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates" );

		wc_get_container()->get( AbilitiesLoader::class )->init();
		self::reset_registries();
		wp_set_current_user( self::$administrator_id );
	}

	/**
	 * Leave the registries and the init count as the next test expects them.
	 */
	public function tearDown(): void {
		global $wp_actions;

		try {
			self::reset_registries();
		} finally {
			parent::tearDown();
		}

		if ( null !== $this->original_init_count ) {
			$wp_actions['init'] = $this->original_init_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		} else {
			unset( $wp_actions['init'] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}
	}

	/**
	 * Run an ability as the current user, with its permission check.
	 *
	 * @param string $name  Ability name.
	 * @param array  $input Ability input.
	 * @return mixed|\WP_Error
	 */
	private function run_ability( string $name, array $input = array() ) {
		$ability = wp_get_ability( $name );
		$this->assertNotNull( $ability, "Ability {$name} should be registered." );

		return $ability->execute( $input );
	}

	/**
	 * Drop every registered ability, so the next read registers them again.
	 */
	private static function reset_registries(): void {
		global $wp_actions;

		foreach ( array( 'WP_Abilities_Registry', 'WP_Ability_Categories_Registry' ) as $registry ) {
			if ( class_exists( $registry ) ) {
				$instance = new \ReflectionProperty( $registry, 'instance' );
				$instance->setAccessible( true );
				$instance->setValue( null, null );
			}
		}
		unset( $wp_actions['wp_abilities_api_init'], $wp_actions['wp_abilities_api_categories_init'] );
	}

	/**
	 * Create a rate through the ability.
	 *
	 * @param array $input Ability input.
	 * @return array The created rate.
	 */
	private function create_rate( array $input ): array {
		$result = $this->run_ability( 'woocommerce/tax-rate-create', $input );
		$this->assertNotWPError( $result );

		return $result['rate'];
	}

	/**
	 * @testdox Should create a rate that tax-rates-query returns as the create returned it.
	 */
	public function test_create_reads_back_through_the_query(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '7',
				'country'   => 'de',
				'state'     => '',
				'postcodes' => array( '10115', '10117' ),
				'cities'    => array( 'Berlin' ),
				'name'      => 'MwSt reduced',
				'priority'  => 2,
				'compound'  => true,
				'shipping'  => false,
				'class'     => 'reduced-rate',
			)
		);

		$this->assertSame(
			array(
				'id'        => $rate['id'],
				'country'   => 'DE',
				'state'     => '',
				'postcodes' => array( '10115', '10117' ),
				'cities'    => array( 'BERLIN' ),
				'rate'      => '7.0000',
				'name'      => 'MwSt reduced',
				'priority'  => 2,
				'compound'  => true,
				'shipping'  => false,
				'class'     => 'reduced-rate',
			),
			$rate,
			'The create should return the stored rate.'
		);

		$query = $this->run_ability( 'woocommerce/tax-rates-query', array( 'include' => array( $rate['id'] ) ) );
		$this->assertSame( array( $rate ), $query['rates'], 'tax-rates-query should return the rate as the create did.' );
	}

	/**
	 * @testdox Should give a created rate the defaults of the tax settings screen and place it after the rates of its class.
	 */
	public function test_create_applies_defaults_and_appends(): void {
		$first_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate'       => '19',
				'tax_rate_order' => 5,
			)
		);
		$second   = $this->create_rate( array( 'rate' => '20' ) );

		$this->assertSame( 'standard', $second['class'] );
		$this->assertSame( 1, $second['priority'] );
		$this->assertFalse( $second['compound'] );
		$this->assertTrue( $second['shipping'] );
		$this->assertSame( '', $second['country'] );
		$this->assertSame( 'Tax', $second['name'] );

		$this->assertSame( '6', \WC_Tax::_get_tax_rate( $second['id'] )['tax_rate_order'], 'The new rate should come after the existing rates of its class.' );

		$query = $this->run_ability( 'woocommerce/tax-rates-query' );
		$this->assertSame( array( $first_id, $second['id'] ), array_column( $query['rates'], 'id' ) );
	}

	/**
	 * @testdox Should change only the sent fields of a rate.
	 */
	public function test_partial_update_leaves_other_fields(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '19',
				'country'   => 'DE',
				'postcodes' => array( '10115' ),
				'name'      => 'MwSt',
				'compound'  => true,
			)
		);

		$result = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'   => $rate['id'],
				'rate' => '16',
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( array_merge( $rate, array( 'rate' => '16.0000' ) ), $result['rate'] );
	}

	/**
	 * @testdox Should accept priority 0, so a stored rate with that priority can be sent back.
	 */
	public function test_update_accepts_priority_zero(): void {
		$rate = $this->create_rate( array( 'rate' => '19' ) );

		$result = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'       => $rate['id'],
				'priority' => 0,
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( 0, $result['rate']['priority'] );
	}

	/**
	 * @testdox Should refuse a negative rate.
	 */
	public function test_create_refuses_negative_rate(): void {
		$result = $this->run_ability( 'woocommerce/tax-rate-create', array( 'rate' => '-5' ) );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		$this->assertSame( 0, $this->run_ability( 'woocommerce/tax-rates-query' )['total'], 'No rate should be created.' );
	}

	/**
	 * @testdox Should accept a rate without a leading zero, and refuse one that ends in a point.
	 */
	public function test_create_accepts_a_rate_without_a_leading_zero(): void {
		$this->assertSame( '0.5000', $this->create_rate( array( 'rate' => '.5' ) )['rate'] );

		foreach ( array( '5.', '.' ) as $rate ) {
			$result = $this->run_ability( 'woocommerce/tax-rate-create', array( 'rate' => $rate ) );

			$this->assertWPError( $result, "Rate {$rate} should be refused." );
			$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should store postcodes without spaces or hyphens, as the tax settings screen does, keeping ranges and wildcards.
	 */
	public function test_create_normalizes_postcodes(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '24',
				'country'   => 'GR',
				'postcodes' => array( '106 82', '107-01', '106 *', '106 00...106 99' ),
			)
		);

		$this->assertSame( array( '10682', '10701', '106*', '10600...10699' ), $rate['postcodes'] );
	}

	/**
	 * @testdox Should refuse a postcode or city that would save as nothing or as two entries, and keep the stored ones.
	 */
	public function test_update_refuses_locations_that_lift_the_restriction(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '19',
				'postcodes' => array( '10115' ),
				'cities'    => array( 'BERLIN' ),
			)
		);

		foreach ( array( array( 'postcodes' => array( '-' ) ), array( 'postcodes' => array( '*' ) ), array( 'cities' => array( 'Berlin;Paris' ) ) ) as $locations ) {
			$result = $this->run_ability( 'woocommerce/tax-rate-update', array( 'id' => $rate['id'] ) + $locations );

			$this->assertWPError( $result );
			$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
		}

		$stored = $this->run_ability( 'woocommerce/tax-rates-query', array( 'include' => array( $rate['id'] ) ) )['rates'][0];
		$this->assertSame( array( '10115' ), $stored['postcodes'] );
		$this->assertSame( array( 'BERLIN' ), $stored['cities'] );
	}

	/**
	 * @testdox Should refuse a postcode or city that cleans to nothing or to *, and keep the stored ones.
	 */
	public function test_update_refuses_locations_that_clean_to_nothing(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '19',
				'postcodes' => array( '10115' ),
				'cities'    => array( 'BERLIN' ),
			)
		);

		$cases = array(
			'postcodes[0]' => array( 'postcodes' => array( '<br>' ) ),
			'postcodes[1]' => array( 'postcodes' => array( '10117', '%20' ) ),
			'postcodes[2]' => array( 'postcodes' => array( '10117', '10119', '<b>*</b>' ) ),
			'cities[0]'    => array( 'cities' => array( '<b></b>' ) ),
		);
		foreach ( $cases as $position => $locations ) {
			$result = $this->run_ability( 'woocommerce/tax-rate-update', array( 'id' => $rate['id'] ) + $locations );

			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_tax_rate_location_empty', $result->get_error_code() );
			$this->assertSame( 400, $result->get_error_data()['status'] );
			$this->assertStringStartsWith( $position . ' ', $result->get_error_message(), 'The error should name the refused entry.' );
		}

		$stored = $this->run_ability( 'woocommerce/tax-rates-query', array( 'include' => array( $rate['id'] ) ) )['rates'][0];
		$this->assertSame( array( '10115' ), $stored['postcodes'] );
		$this->assertSame( array( 'BERLIN' ), $stored['cities'] );
	}

	/**
	 * @testdox Should refuse to create a rate whose city cleans to nothing, without saving the rate.
	 */
	public function test_create_refuses_a_location_that_cleans_to_nothing(): void {
		$result = $this->run_ability(
			'woocommerce/tax-rate-create',
			array(
				'rate'   => '19',
				'cities' => array( '<b></b>' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_location_empty', $result->get_error_code() );
		$this->assertStringStartsWith( 'cities[0] ', $result->get_error_message() );
		$this->assertSame( 0, $this->run_ability( 'woocommerce/tax-rates-query' )['total'], 'No rate should be created.' );
	}

	/**
	 * @testdox Should match a customer postcode written with a space to a rate saved with that space.
	 */
	public function test_spaced_postcode_matches_at_checkout(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '24',
				'country'   => 'GR',
				'postcodes' => array( '106 82' ),
			)
		);

		$matched = \WC_Tax::find_rates(
			array(
				'country'   => 'GR',
				'postcode'  => '106 82',
				'state'     => '',
				'city'      => '',
				'tax_class' => '',
			)
		);

		$this->assertArrayHasKey( $rate['id'], $matched );
	}

	/**
	 * @testdox Should clear the postcodes of a rate when an empty list is sent, and keep its cities.
	 */
	public function test_update_clears_postcodes(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '19',
				'postcodes' => array( '10115', '10117' ),
				'cities'    => array( 'BERLIN' ),
			)
		);

		$result = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'        => $rate['id'],
				'postcodes' => array(),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( array(), $result['rate']['postcodes'] );
		$this->assertSame( array( 'BERLIN' ), $result['rate']['cities'] );
	}

	/**
	 * @testdox Should replace only the cities of a rate when only cities are sent.
	 */
	public function test_update_replaces_only_cities(): void {
		$rate = $this->create_rate(
			array(
				'rate'      => '19',
				'country'   => 'DE',
				'postcodes' => array( '10115' ),
				'cities'    => array( 'Berlin' ),
				'name'      => 'MwSt',
			)
		);
		set_transient( 'shipping-transient-version', 'before-update' );

		$result = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'     => $rate['id'],
				'cities' => array( 'Potsdam', 'Hamburg' ),
			)
		);

		$this->assertNotWPError( $result );
		$this->assertSame( array_merge( $rate, array( 'cities' => array( 'POTSDAM', 'HAMBURG' ) ) ), $result['rate'] );
		$this->assertNotSame( 'before-update', get_transient( 'shipping-transient-version' ), 'An update of only the cities should refresh saved shipping rates.' );
	}

	/**
	 * @testdox Should return the rate unchanged, without a write, when only the id is sent.
	 */
	public function test_update_with_only_id_returns_the_rate(): void {
		$rate    = $this->create_rate( array( 'rate' => '19' ) );
		$updated = 0;
		add_action(
			'woocommerce_tax_rate_updated',
			static function () use ( &$updated ) {
				++$updated;
			}
		);

		$result = $this->run_ability( 'woocommerce/tax-rate-update', array( 'id' => $rate['id'] ) );

		$this->assertSame( array( 'rate' => $rate ), $result );
		$this->assertSame( 0, $updated, 'No write should happen.' );
	}

	/**
	 * @testdox Should refuse a tax class that does not exist, on create and on update.
	 */
	public function test_unknown_class_is_refused(): void {
		$create = $this->run_ability(
			'woocommerce/tax-rate-create',
			array(
				'rate'  => '19',
				'class' => 'no-such-class',
			)
		);

		$this->assertWPError( $create );
		$this->assertSame( 'woocommerce_ability_tax_rate_class_not_found', $create->get_error_code() );
		$this->assertSame( 400, $create->get_error_data()['status'] );
		$this->assertSame( 0, $this->run_ability( 'woocommerce/tax-rates-query' )['total'], 'No rate should be created.' );

		$rate   = $this->create_rate( array( 'rate' => '19' ) );
		$update = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'    => $rate['id'],
				'rate'  => '7',
				'class' => 'no-such-class',
			)
		);

		$this->assertWPError( $update );
		$this->assertSame( 'woocommerce_ability_tax_rate_class_not_found', $update->get_error_code() );
		$this->assertSame( '19.0000', $this->run_ability( 'woocommerce/tax-rates-query' )['rates'][0]['rate'], 'The rate should not change.' );
	}

	/**
	 * @testdox Should move a rate to another tax class, and back to the standard rates when standard is sent.
	 */
	public function test_update_moves_a_rate_between_classes(): void {
		$rate = $this->create_rate( array( 'rate' => '19' ) );

		$reduced = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'    => $rate['id'],
				'class' => 'reduced-rate',
			)
		);
		$this->assertNotWPError( $reduced );
		$this->assertSame( 'reduced-rate', $reduced['rate']['class'] );

		$standard = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'    => $rate['id'],
				'class' => 'standard',
			)
		);
		$this->assertNotWPError( $standard );
		$this->assertSame( $rate, $standard['rate'] );
		$this->assertSame( '', \WC_Tax::_get_tax_rate( $rate['id'] )['tax_rate_class'], 'The standard rates should be stored as an empty class.' );
	}

	/**
	 * @testdox Should refuse country $country, which is not a valid country code.
	 *
	 * @testWith ["XX"]
	 *           ["UK"]
	 *
	 * @param string $country Country code.
	 */
	public function test_create_refuses_an_unknown_country( string $country ): void {
		$result = $this->run_ability(
			'woocommerce/tax-rate-create',
			array(
				'rate'    => '20',
				'country' => $country,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_country_invalid', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 0, $this->run_ability( 'woocommerce/tax-rates-query' )['total'], 'No rate should be created.' );
	}

	/**
	 * @testdox Should take a state from the country's list, and any state for a country without one.
	 */
	public function test_create_checks_the_state_against_the_country(): void {
		$refused = $this->run_ability(
			'woocommerce/tax-rate-create',
			array(
				'rate'    => '7.25',
				'country' => 'US',
				'state'   => 'California',
			)
		);
		$this->assertWPError( $refused );
		$this->assertSame( 'woocommerce_ability_tax_rate_state_invalid', $refused->get_error_code() );
		$this->assertStringStartsWith( '"California" is not a state of US.', $refused->get_error_message(), 'The error should quote the state as it was sent.' );
		$this->assertStringContainsString( 'CA', $refused->get_error_message(), 'The error should list the valid codes.' );

		$this->assertSame(
			'CA',
			$this->create_rate(
				array(
					'rate'    => '7.25',
					'country' => 'US',
					'state'   => 'ca',
				)
			)['state']
		);
		$this->assertSame(
			'TIROL',
			$this->create_rate(
				array(
					'rate'    => '20',
					'country' => 'AT',
					'state'   => 'Tirol',
				)
			)['state'],
			'A country without a state list should take any state.'
		);
	}

	/**
	 * @testdox Should check the stored state against a new country, but not a stored region when other fields change.
	 */
	public function test_update_checks_the_region_it_will_save(): void {
		$rate   = $this->create_rate(
			array(
				'rate'    => '7.25',
				'country' => 'US',
				'state'   => 'CA',
			)
		);
		$result = $this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'      => $rate['id'],
				'country' => 'DE',
			)
		);
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_state_invalid', $result->get_error_code() );
		$this->assertSame( 'US', $this->run_ability( 'woocommerce/tax-rates-query', array( 'include' => array( $rate['id'] ) ) )['rates'][0]['country'], 'The rate should not change.' );

		$stored_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate'         => '19',
				'tax_rate_country' => 'XX',
				'tax_rate_order'   => 0,
			)
		);
		$this->assertNotWPError(
			$this->run_ability(
				'woocommerce/tax-rate-update',
				array(
					'id'   => $stored_id,
					'rate' => '7',
				)
			)
		);
	}

	/**
	 * @testdox Should report the rate as saved without its locations when $ability fails to write them.
	 *
	 * @testWith ["woocommerce/tax-rate-create"]
	 *           ["woocommerce/tax-rate-update"]
	 *
	 * @param string $ability Ability name.
	 */
	public function test_failed_location_write_is_reported( string $ability ): void {
		global $wpdb;

		$input = array( 'postcodes' => array( '10117' ) );
		if ( 'woocommerce/tax-rate-update' === $ability ) {
			$input['id'] = $this->create_rate(
				array(
					'rate'      => '19',
					'postcodes' => array( '10115' ),
				)
			)['id'];
		} else {
			$input['rate'] = '19';
		}
		$locations_insert = "INSERT INTO {$wpdb->prefix}woocommerce_tax_rate_locations ";
		add_filter(
			'query',
			static function ( $query ) use ( $locations_insert ) {
				return 0 === strpos( $query, $locations_insert ) ? 'INSERT INTO no_such_table_for_the_locations VALUES (1)' : $query;
			}
		);

		$suppressed = $wpdb->suppress_errors( true );
		try {
			$result = $this->run_ability( $ability, $input );
		} finally {
			$wpdb->suppress_errors( $suppressed );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_locations_failed', $result->get_error_code() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
		$rate_id = $result->get_error_data()['rate_id'];
		$this->assertSame( array(), $this->run_ability( 'woocommerce/tax-rates-query', array( 'include' => array( $rate_id ) ) )['rates'][0]['postcodes'], 'The error should name the rate that lost its postcodes.' );
	}

	/**
	 * @testdox Should report an unknown rate ID as not found on $ability.
	 *
	 * @testWith ["woocommerce/tax-rate-update"]
	 *           ["woocommerce/tax-rate-delete"]
	 *
	 * @param string $ability Ability name.
	 */
	public function test_unknown_id_is_not_found( string $ability ): void {
		$result = $this->run_ability( $ability, array( 'id' => PHP_INT_MAX ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_not_found', $result->get_error_code() );
		$this->assertSame( 404, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should delete a rate with its locations and return it as it was.
	 */
	public function test_delete_returns_the_old_rate(): void {
		global $wpdb;

		$rate = $this->create_rate(
			array(
				'rate'      => '19',
				'country'   => 'DE',
				'postcodes' => array( '10115' ),
				'cities'    => array( 'BERLIN' ),
			)
		);

		$result = $this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate['id'] ) );

		$this->assertSame( array( 'rate' => $rate ), $result );
		$this->assertSame( 0, $this->run_ability( 'woocommerce/tax-rates-query' )['total'], 'The rate should be gone.' );
		$this->assertSame(
			'0',
			$wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}woocommerce_tax_rate_locations WHERE tax_rate_id = %d", $rate['id'] ) ),
			'The locations of the rate should be gone.'
		);
	}

	/**
	 * @testdox Should make carts recalculate shipping taxes after a create, update or delete, but not after an update that changes nothing.
	 */
	public function test_writes_refresh_saved_shipping_rates(): void {
		set_transient( 'shipping-transient-version', 'before-create' );
		$rate = $this->create_rate( array( 'rate' => '25' ) );
		$this->assertNotSame( 'before-create', get_transient( 'shipping-transient-version' ), 'A create should refresh saved shipping rates.' );

		set_transient( 'shipping-transient-version', 'before-noop' );
		$this->run_ability( 'woocommerce/tax-rate-update', array( 'id' => $rate['id'] ) );
		$this->assertSame( 'before-noop', get_transient( 'shipping-transient-version' ), 'An update that changes nothing should keep saved shipping rates.' );

		set_transient( 'shipping-transient-version', 'before-update' );
		$this->run_ability(
			'woocommerce/tax-rate-update',
			array(
				'id'        => $rate['id'],
				'postcodes' => array( '10115' ),
			)
		);
		$this->assertNotSame( 'before-update', get_transient( 'shipping-transient-version' ), 'An update of only the postcodes should refresh saved shipping rates.' );

		set_transient( 'shipping-transient-version', 'before-delete' );
		$this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate['id'] ) );
		$this->assertNotSame( 'before-delete', get_transient( 'shipping-transient-version' ), 'A delete should refresh saved shipping rates.' );
	}

	/**
	 * @testdox Should report an error, not the deleted rate, when the rate row is not removed.
	 */
	public function test_delete_reports_a_rate_row_that_was_not_removed(): void {
		global $wpdb;

		$rate         = $this->create_rate( array( 'rate' => '19' ) );
		$rates_delete = "DELETE FROM {$wpdb->prefix}woocommerce_tax_rates WHERE ";
		add_filter(
			'query',
			static function ( $query ) use ( $rates_delete ) {
				return str_replace( $rates_delete, $rates_delete . '1 = 0 AND ', $query );
			}
		);

		$result = $this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate['id'] ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_delete_failed', $result->get_error_code() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	/**
	 * @testdox Should report an error when the check for the rate row fails, and still make carts recalculate shipping taxes.
	 */
	public function test_delete_reports_a_failed_check_of_the_rate_row(): void {
		global $wpdb;

		$rate        = $this->create_rate( array( 'rate' => '19' ) );
		$rates_table = "{$wpdb->prefix}woocommerce_tax_rates";
		$hook_ran    = false;
		add_action(
			'woocommerce_tax_rate_deleted',
			static function () use ( &$hook_ran ) {
				$hook_ran = true;
			}
		);
		add_filter(
			'query',
			static function ( $query ) use ( &$hook_ran, $rates_table ) {
				if ( $hook_ran && false !== strpos( $query, "FROM {$rates_table}" ) ) {
					return 'SELECT * FROM no_such_table_for_the_check';
				}
				return $query;
			}
		);
		set_transient( 'shipping-transient-version', 'before-delete' );

		$suppressed = $wpdb->suppress_errors( true );
		try {
			$result = $this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate['id'] ) );
		} finally {
			$wpdb->suppress_errors( $suppressed );
		}

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_tax_rate_delete_failed', $result->get_error_code() );
		$this->assertNotSame( 'before-delete', get_transient( 'shipping-transient-version' ), 'Saved shipping rates should be refreshed even when the check fails.' );
	}

	/**
	 * @testdox Should report a delete as done when a deleted-hook callback runs a query of its own.
	 */
	public function test_delete_succeeds_when_the_deleted_hook_runs_a_query(): void {
		global $wpdb;

		$rate = $this->create_rate( array( 'rate' => '19' ) );
		add_action(
			'woocommerce_tax_rate_deleted',
			static function () use ( $wpdb ) {
				$wpdb->get_var( 'SELECT 1' );
			}
		);

		$result = $this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate['id'] ) );

		$this->assertSame( array( 'rate' => $rate ), $result );
	}

	/**
	 * @testdox Should let a shop manager create, update and delete rates.
	 */
	public function test_shop_manager_can_write(): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );

		$rate = $this->create_rate( array( 'rate' => '19' ) );
		$this->assertNotWPError(
			$this->run_ability(
				'woocommerce/tax-rate-update',
				array(
					'id'   => $rate['id'],
					'rate' => '7',
				)
			)
		);
		$this->assertNotWPError( $this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate['id'] ) ) );
	}

	/**
	 * @testdox Should refuse tax rate writes to a customer.
	 */
	public function test_customer_is_refused(): void {
		$rate_id = \WC_Tax::_insert_tax_rate(
			array(
				'tax_rate'       => '19.0000',
				'tax_rate_order' => 0,
			)
		);
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$results = array(
			$this->run_ability( 'woocommerce/tax-rate-create', array( 'rate' => '7' ) ),
			$this->run_ability(
				'woocommerce/tax-rate-update',
				array(
					'id'   => $rate_id,
					'rate' => '7',
				)
			),
			$this->run_ability( 'woocommerce/tax-rate-delete', array( 'id' => $rate_id ) ),
		);

		foreach ( $results as $result ) {
			$this->assertWPError( $result );
			$this->assertSame( 'ability_invalid_permissions', $result->get_error_code() );
		}
		$this->assertSame( '19.0000', \WC_Tax::_get_tax_rate( $rate_id )['tax_rate'], 'The rate should not change.' );
	}
}
