<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\TaxBasedOn;
use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests opt-in setting writes.
 */
class SettingsUpdateTest extends \WC_Unit_Test_Case {

	/**
	 * The System Under Test.
	 *
	 * @var \WP_Ability
	 */
	private $sut;

	/**
	 * Register abilities and authenticate a manager.
	 */
	public function setUp(): void {
		parent::setUp();
		add_action( 'wp_abilities_api_init', array( AbilitiesLoader::class, 'register_abilities' ) );
		do_action( 'wp_abilities_api_init' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
		$this->sut = wp_get_ability( 'woocommerce/settings-update' );
	}

	/**
	 * Adds a field to the general settings page.
	 *
	 * @param array $field Field definition merged over a writable text field.
	 */
	private function add_general_field( array $field ): void {
		add_filter(
			'woocommerce_general_settings',
			static function ( $settings ) use ( $field ) {
				$settings[] = $field + array(
					'id'        => 'extension_field',
					'type'      => 'text',
					'default'   => '',
					'abilities' => array(
						'read'  => true,
						'write' => true,
					),
				);
				return $settings;
			}
		);
	}

	/**
	 * Builds an update input, sending lists in `values` and everything else in `value`.
	 *
	 * @param string $group  Settings group.
	 * @param array  $values Setting IDs mapped to new values.
	 * @return array
	 */
	private function input( string $group, array $values ): array {
		$settings = array();
		foreach ( $values as $id => $value ) {
			$field      = is_array( $value ) && array_values( $value ) === $value ? 'values' : 'value';
			$settings[] = array(
				'id'   => $id,
				$field => $value,
			);
		}
		return array(
			'group'    => $group,
			'settings' => $settings,
		);
	}

	/**
	 * @testdox Should update an opted-in Core field and return the previous value.
	 */
	public function test_updates_core_field(): void {
		update_option( 'woocommerce_currency', 'USD' );

		$result = $this->sut->execute( $this->input( 'general', array( 'woocommerce_currency' => 'EUR' ) ) );

		$this->assertSame(
			array(
				'settings' => array(
					array(
						'id'       => 'woocommerce_currency',
						'previous' => 'USD',
						'value'    => 'EUR',
					),
				),
			),
			$result
		);
		$this->assertSame( 'EUR', get_option( 'woocommerce_currency' ) );
	}

	/**
	 * @testdox Should refuse fields without a strict read and write opt-in, and passwords.
	 * @testWith ["text", {"read": true}]
	 *           ["text", {"write": true}]
	 *           ["text", {"read": true, "write": "yes"}]
	 *           ["text", {"read": true, "write": 1}]
	 *           ["text", {"read": 1, "write": true}]
	 *           ["text", null]
	 *           ["password", {"read": true, "write": true}]
	 * @param string     $type      Field type.
	 * @param array|null $abilities Field metadata.
	 */
	public function test_refuses_fields_without_opt_in( string $type, ?array $abilities ): void {
		update_option( 'extension_field', 'secret-sentinel' );
		$this->add_general_field(
			array(
				'type'      => $type,
				'abilities' => $abilities,
			)
		);

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => 'changed' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_not_writable', $result->get_error_code() );
		$this->assertSame( 403, $result->get_error_data()['status'] );
		$this->assertSame( array( 'extension_field' ), $result->get_error_data()['ids'] );
		$this->assertSame( 'secret-sentinel', get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should refuse unknown IDs the same way as withheld ones.
	 */
	public function test_refuses_unknown_ids(): void {
		$result = $this->sut->execute( $this->input( 'general', array( 'missing_field' => 'value' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_not_writable', $result->get_error_code() );
		$this->assertSame( array( 'missing_field' ), $result->get_error_data()['ids'] );
	}

	/**
	 * @testdox Should refuse fields whose value comes from the definition instead of a stored option.
	 * @testWith [{"fixed_value": "no"}]
	 *           [{"value": "supplied"}]
	 * @param array $metadata Value metadata.
	 */
	public function test_refuses_supplied_values( array $metadata ): void {
		update_option( 'extension_field', 'stored' );
		$this->add_general_field( $metadata );

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => 'changed' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_not_writable', $result->get_error_code() );
		$this->assertSame( 'stored', get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should refuse additional tax classes, which are not stored as an option.
	 */
	public function test_refuses_tax_classes(): void {
		$result = $this->sut->execute( $this->input( 'tax', array( 'woocommerce_tax_classes' => 'Reduced' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_not_writable', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse personal data retention periods, which let a scheduled job trash orders, anonymize orders or delete accounts.
	 * @testWith ["woocommerce_delete_inactive_accounts"]
	 *           ["woocommerce_trash_pending_orders"]
	 *           ["woocommerce_trash_failed_orders"]
	 *           ["woocommerce_trash_cancelled_orders"]
	 *           ["woocommerce_anonymize_refunded_orders"]
	 *           ["woocommerce_anonymize_completed_orders"]
	 *
	 * @param string $id Retention setting ID.
	 */
	public function test_refuses_retention_periods( string $id ): void {
		delete_option( $id );

		$result = $this->sut->execute(
			$this->input(
				'account',
				array(
					$id => array(
						'number' => '1',
						'unit'   => 'days',
					),
				)
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_not_writable', $result->get_error_code() );
		$this->assertFalse( get_option( $id ) );
	}

	/**
	 * @testdox Should update an extension field registered directly on a custom group.
	 */
	public function test_updates_extension_group_field(): void {
		add_filter(
			'woocommerce_settings_groups',
			static function ( $groups ) {
				$groups[] = array(
					'id'    => 'extension',
					'label' => 'Extension',
				);
				return $groups;
			}
		);
		add_filter(
			'woocommerce_settings-extension',
			static function ( $settings ) {
				$settings[] = array(
					'id'         => 'mode',
					'type'       => 'select',
					'option_key' => 'extension_mode',
					'default'    => 'sandbox',
					'options'    => array(
						'sandbox' => 'Sandbox',
						'live'    => 'Live',
					),
					'abilities'  => array(
						'read'  => true,
						'write' => true,
					),
				);
				return $settings;
			}
		);

		$result = $this->sut->execute( $this->input( 'extension', array( 'mode' => 'live' ) ) );

		$this->assertSame( 'sandbox', $result['settings'][0]['previous'] );
		$this->assertSame( 'live', $result['settings'][0]['value'] );
		$this->assertSame( 'live', get_option( 'extension_mode' ) );
		$this->assertFalse( get_option( 'mode' ), 'The value should be saved under the option key, not the field ID.' );
	}

	/**
	 * @testdox Should update an extension field added to a Core settings page and run admin sanitize filters.
	 */
	public function test_updates_extension_page_field(): void {
		$this->add_general_field( array() );
		add_filter( 'woocommerce_admin_settings_sanitize_option_extension_field', 'strtoupper' );

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => 'compact' ) ) );

		$this->assertSame( '', $result['settings'][0]['previous'] );
		$this->assertSame( 'COMPACT', $result['settings'][0]['value'] );
		$this->assertSame( 'COMPACT', get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should refuse values the REST settings validators or field types reject.
	 * @testWith ["select", "not-an-option"]
	 *           ["select", ["sandbox"]]
	 *           ["checkbox", "maybe"]
	 *           ["text", true]
	 *           ["number", "abc"]
	 *           ["multiselect", ["sandbox", "unknown"]]
	 *           ["relative_date_selector", {"number": "3", "unit": "decades"}]
	 * @param string $type  Field type.
	 * @param mixed  $value Requested value.
	 */
	public function test_refuses_invalid_values( string $type, $value ): void {
		update_option( 'extension_field', 'stored' );
		$this->add_general_field(
			array(
				'type'    => $type,
				'options' => array(
					'sandbox' => 'Sandbox',
					'live'    => 'Live',
				),
			)
		);

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => $value ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( array( 'extension_field' ), $result->get_error_data()['ids'] );
		$this->assertSame( 'stored', get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should save values in the stored form of their field type.
	 * @testWith ["checkbox", true, "yes"]
	 *           ["checkbox", false, "no"]
	 *           ["number", 15, "15"]
	 *           ["multiselect", ["live", "sandbox"], ["live", "sandbox"]]
	 *           ["relative_date_selector", {"number": "3", "unit": "weeks"}, {"number": 3, "unit": "weeks"}]
	 * @param string $type     Field type.
	 * @param mixed  $value    Requested value.
	 * @param mixed  $expected Stored value.
	 */
	public function test_normalizes_values( string $type, $value, $expected ): void {
		$this->add_general_field(
			array(
				'type'    => $type,
				'options' => array(
					'sandbox' => 'Sandbox',
					'live'    => 'Live',
				),
			)
		);

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => $value ) ) );

		$this->assertIsArray( $result );
		$this->assertSame( $expected, $result['settings'][0]['value'] );
		$this->assertSame( $expected, get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should refuse list entries the admin save would drop, even when they are valid choices.
	 * @testWith [["0"]]
	 *           [["0", "live"]]
	 * @param array $values Requested list.
	 */
	public function test_refuses_list_entries_admin_save_drops( array $values ): void {
		update_option( 'extension_field', array( 'live' ) );
		$this->add_general_field(
			array(
				'type'    => 'multiselect',
				'options' => array(
					'0'    => 'None',
					'live' => 'Live',
				),
			)
		);

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => $values ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
		$this->assertSame( array( 'live' ), get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should save an empty list.
	 */
	public function test_saves_empty_list(): void {
		update_option( 'extension_field', array( 'live' ) );
		$this->add_general_field(
			array(
				'type'    => 'multiselect',
				'options' => array(
					'0'    => 'None',
					'live' => 'Live',
				),
			)
		);

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => array() ) ) );

		$this->assertIsArray( $result );
		$this->assertSame( array(), $result['settings'][0]['value'] );
		$this->assertSame( array(), get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should validate country fields against store countries and states.
	 * @testWith ["single_select_country", "US:CA", true]
	 *           ["single_select_country", "US", false]
	 *           ["single_select_country", "XX", false]
	 *           ["multi_select_countries", ["DE", "FR"], true]
	 *           ["multi_select_countries", ["DE", "XX"], false]
	 * @param string $type  Field type.
	 * @param mixed  $value Requested value.
	 * @param bool   $valid Whether the value is accepted.
	 */
	public function test_country_values( string $type, $value, bool $valid ): void {
		$this->add_general_field( array( 'type' => $type ) );

		$result = $this->sut->execute( $this->input( 'general', array( 'extension_field' => $value ) ) );

		if ( $valid ) {
			$this->assertSame( $value, get_option( 'extension_field' ) );
		} else {
			$this->assertWPError( $result );
			$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
		}
	}

	/**
	 * @testdox Should change nothing when any item in a batch is refused.
	 * @testWith ["woocommerce_default_country", "invalid-country", "woocommerce_ability_settings_invalid_value"]
	 *           ["missing_field", "Paris", "woocommerce_ability_settings_not_writable"]
	 * @param string $bad_id    ID of the refused item.
	 * @param string $bad_value Value of the refused item.
	 * @param string $code      Expected error code.
	 */
	public function test_batch_is_all_or_nothing( string $bad_id, string $bad_value, string $code ): void {
		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_store_city', 'Berlin' );
		update_option( 'woocommerce_default_country', 'DE' );
		$values = array(
			'woocommerce_currency'   => 'EUR',
			'woocommerce_store_city' => 'Paris',
			$bad_id                  => $bad_value,
		);

		$result = $this->sut->execute( $this->input( 'general', $values ) );

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( array( $bad_id ), $result->get_error_data()['ids'] );
		$this->assertStringContainsString( $bad_id, $result->get_error_message() );
		$this->assertStringNotContainsString( 'woocommerce_currency', $result->get_error_message() );
		$this->assertSame( 'USD', get_option( 'woocommerce_currency' ) );
		$this->assertSame( 'Berlin', get_option( 'woocommerce_store_city' ) );
		$this->assertSame( 'DE', get_option( 'woocommerce_default_country' ) );
	}

	/**
	 * @testdox Should refuse an item that sends both value and values, or neither.
	 * @testWith [{"value": "EUR", "values": ["EUR"]}]
	 *           [{}]
	 * @param array $fields Value fields of the item.
	 */
	public function test_refuses_items_without_exactly_one_value_field( array $fields ): void {
		update_option( 'woocommerce_currency', 'USD' );

		$result = $this->sut->execute(
			array(
				'group'    => 'general',
				'settings' => array( array( 'id' => 'woocommerce_currency' ) + $fields ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
		$this->assertSame( array( 'woocommerce_currency' ), $result->get_error_data()['ids'] );
		$this->assertSame( 'USD', get_option( 'woocommerce_currency' ) );
	}

	/**
	 * @testdox Should name the expected field when a setting is sent in the wrong one.
	 * @testWith ["multiselect", "value", "live", "values"]
	 *           ["text", "values", ["live"], "value"]
	 * @param string $type     Field type.
	 * @param string $field    Value field the item uses.
	 * @param mixed  $value    Requested value.
	 * @param string $expected Value field the setting takes.
	 */
	public function test_names_expected_field_for_wrong_field( string $type, string $field, $value, string $expected ): void {
		update_option( 'extension_field', 'stored' );
		$this->add_general_field(
			array(
				'type'    => $type,
				'options' => array( 'live' => 'Live' ),
			)
		);

		$result = $this->sut->execute(
			array(
				'group'    => 'general',
				'settings' => array(
					array(
						'id'   => 'extension_field',
						$field => $value,
					),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
		$this->assertSame( array( 'extension_field' => $expected ), $result->get_error_data()['expected'] );
		$this->assertStringContainsString( "in {$expected}", $result->get_error_message() );
		$this->assertStringContainsString( 'extension_field', $result->get_error_message() );
		$this->assertSame( 'stored', get_option( 'extension_field' ) );
	}

	/**
	 * @testdox Should refuse a list in value through the input schema, before any setting is looked up.
	 */
	public function test_input_schema_refuses_list_in_value(): void {
		$result = $this->sut->execute(
			array(
				'group'    => 'general',
				'settings' => array(
					array(
						'id'    => 'woocommerce_specific_allowed_countries',
						'value' => array( 'DE' ),
					),
				),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	/**
	 * @testdox Should restore a list setting by sending its previous value back in values.
	 */
	public function test_restores_list_from_previous(): void {
		update_option( 'woocommerce_specific_allowed_countries', array( 'DE', 'FR' ) );
		$changed = $this->sut->execute( $this->input( 'general', array( 'woocommerce_specific_allowed_countries' => array( 'AT' ) ) ) );
		$this->assertIsArray( $changed );

		$restored = $this->sut->execute( $this->input( 'general', array( 'woocommerce_specific_allowed_countries' => $changed['settings'][0]['previous'] ) ) );

		$this->assertIsArray( $restored );
		$this->assertSame( array( 'DE', 'FR' ), get_option( 'woocommerce_specific_allowed_countries' ) );
	}

	/**
	 * @testdox Should accept only published pages, or an empty value, for page settings.
	 */
	public function test_page_values(): void {
		$page    = self::factory()->post->create( array( 'post_type' => 'page' ) );
		$draft   = self::factory()->post->create(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
			)
		);
		$product = self::factory()->post->create( array( 'post_type' => 'product' ) );
		update_option( 'woocommerce_terms_page_id', '' );

		foreach ( array( 'abc', (string) $draft, (string) $product, '999999' ) as $value ) {
			$result = $this->sut->execute( $this->input( 'advanced', array( 'woocommerce_terms_page_id' => $value ) ) );
			$this->assertWPError( $result, "{$value} should be refused." );
			$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
		}
		$this->assertSame( '', get_option( 'woocommerce_terms_page_id' ) );

		$result = $this->sut->execute( $this->input( 'advanced', array( 'woocommerce_terms_page_id' => $page ) ) );
		$this->assertIsArray( $result );
		$this->assertSame( (string) $page, get_option( 'woocommerce_terms_page_id' ) );

		$result = $this->sut->execute( $this->input( 'advanced', array( 'woocommerce_terms_page_id' => '' ) ) );
		$this->assertIsArray( $result );
		$this->assertSame( '', get_option( 'woocommerce_terms_page_id' ) );
	}

	/**
	 * @testdox Should restore an unset checkout page from the previous value it reports.
	 */
	public function test_restores_unset_checkout_page_from_previous(): void {
		$page = self::factory()->post->create( array( 'post_type' => 'page' ) );
		delete_option( 'woocommerce_checkout_page_id' );

		$changed = $this->sut->execute( $this->input( 'advanced', array( 'woocommerce_checkout_page_id' => $page ) ) );
		$this->assertIsArray( $changed );
		$this->assertSame( -1, $changed['settings'][0]['previous'] );

		$restored = $this->sut->execute( $this->input( 'advanced', array( 'woocommerce_checkout_page_id' => $changed['settings'][0]['previous'] ) ) );
		$this->assertIsArray( $restored );
		$this->assertSame( '', get_option( 'woocommerce_checkout_page_id' ) );
		$this->assertSame( -1, wc_get_page_id( 'checkout' ) );

		$result = $this->sut->execute( $this->input( 'advanced', array( 'woocommerce_checkout_page_id' => '-2' ) ) );
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_invalid_value', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse page settings that would share a page, against both the batch and the stored pages.
	 * @testWith ["woocommerce_checkout_page_id", "woocommerce_cart_page_id"]
	 *           ["woocommerce_myaccount_page_id", "woocommerce_checkout_page_id"]
	 *           ["woocommerce_terms_page_id", "woocommerce_checkout_page_id"]
	 * @param string $requested Page setting the batch changes.
	 * @param string $stored    Page setting that already uses the page.
	 */
	public function test_refuses_shared_pages( string $requested, string $stored ): void {
		$page = (string) self::factory()->post->create( array( 'post_type' => 'page' ) );
		update_option( $stored, $page );
		update_option( $requested, '' );

		$result = $this->sut->execute( $this->input( 'advanced', array( $requested => $page ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_page_conflict', $result->get_error_code() );
		$this->assertSame( array( $requested ), $result->get_error_data()['ids'] );
		$this->assertSame( '', get_option( $requested ) );

		$other  = (string) self::factory()->post->create( array( 'post_type' => 'page' ) );
		$result = $this->sut->execute(
			$this->input(
				'advanced',
				array(
					$requested => $other,
					$stored    => $other,
				)
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_page_conflict', $result->get_error_code() );
		$this->assertSame( $page, get_option( $stored ) );
	}

	/**
	 * @testdox Should queue a rewrite rules flush when an endpoint slug changes, and only then.
	 */
	public function test_endpoint_change_queues_rewrite_flush(): void {
		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );

		$this->sut->execute( $this->input( 'advanced', array( 'woocommerce_myaccount_orders_endpoint' => 'my-orders' ) ) );

		$this->assertSame( 'my-orders', get_option( 'woocommerce_myaccount_orders_endpoint' ) );
		$this->assertSame( 'yes', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );

		update_option( 'woocommerce_queue_flush_rewrite_rules', 'no' );
		$this->sut->execute( $this->input( 'general', array( 'woocommerce_currency' => 'EUR' ) ) );

		$this->assertSame( 'no', get_option( 'woocommerce_queue_flush_rewrite_rules' ) );
	}

	/**
	 * @testdox Should recount product terms when out of stock visibility changes, and only then.
	 */
	public function test_out_of_stock_visibility_change_recounts_terms(): void {
		update_option( 'woocommerce_hide_out_of_stock_items', 'no' );
		$term_id = (int) wp_insert_term( 'Recount category', 'product_cat' )['term_id'];
		$product = \WC_Helper_Product::create_simple_product();
		$product->set_category_ids( array( $term_id ) );
		$product->set_stock_status( ProductStockStatus::OUT_OF_STOCK );
		$product->save();
		wc_recount_all_terms( false );
		$this->assertSame( 1, (int) get_term_meta( $term_id, 'product_count_product_cat', true ) );

		$recounts = 0;
		$counter  = static function ( $recount ) use ( &$recounts ) {
			++$recounts;
			return $recount;
		};
		add_filter( 'woocommerce_product_recount_terms', $counter );

		$this->sut->execute( $this->input( 'products', array( 'woocommerce_hide_out_of_stock_items' => false ) ) );
		$this->assertSame( 0, $recounts );

		$this->sut->execute( $this->input( 'products', array( 'woocommerce_hide_out_of_stock_items' => true ) ) );
		remove_filter( 'woocommerce_product_recount_terms', $counter );

		$this->assertSame( 'yes', get_option( 'woocommerce_hide_out_of_stock_items' ) );
		$this->assertSame( 0, (int) get_term_meta( $term_id, 'product_count_product_cat', true ) );
	}

	/**
	 * @testdox Should invalidate cached shipping rates after shipping or tax changes, and only then.
	 */
	public function test_shipping_and_tax_changes_invalidate_shipping_rates(): void {
		set_transient( 'shipping-transient-version', 'stale' );
		$this->sut->execute( $this->input( 'shipping', array( 'woocommerce_shipping_hide_rates_when_free' => true ) ) );

		$this->assertSame( 'yes', get_option( 'woocommerce_shipping_hide_rates_when_free' ) );
		$this->assertNotSame( 'stale', \WC_Cache_Helper::get_transient_version( 'shipping' ) );

		set_transient( 'shipping-transient-version', 'stale' );
		$this->sut->execute( $this->input( 'tax', array( 'woocommerce_tax_based_on' => TaxBasedOn::BILLING ) ) );

		$this->assertSame( TaxBasedOn::BILLING, get_option( 'woocommerce_tax_based_on' ) );
		$this->assertNotSame( 'stale', \WC_Cache_Helper::get_transient_version( 'shipping' ) );

		set_transient( 'shipping-transient-version', 'stale' );
		$this->sut->execute( $this->input( 'general', array( 'woocommerce_currency' => 'EUR' ) ) );

		$this->assertSame( 'stale', \WC_Cache_Helper::get_transient_version( 'shipping' ) );
	}

	/**
	 * @testdox Should refuse a batch that names the same setting twice.
	 */
	public function test_refuses_duplicate_ids(): void {
		$input               = $this->input( 'general', array( 'woocommerce_currency' => 'EUR' ) );
		$input['settings'][] = array(
			'id'    => 'woocommerce_currency',
			'value' => 'GBP',
		);
		update_option( 'woocommerce_currency', 'USD' );

		$result = $this->sut->execute( $input );

		$this->assertWPError( $result );
		$this->assertSame( array( 'woocommerce_currency' ), $result->get_error_data()['ids'] );
		$this->assertSame( 'USD', get_option( 'woocommerce_currency' ) );
	}

	/**
	 * @testdox Should update one member of an email settings option without touching the others.
	 */
	public function test_updates_array_option_key(): void {
		update_option(
			'woocommerce_new_order_settings',
			array(
				'subject'   => 'Old subject',
				'recipient' => 'private@example.test',
			)
		);

		$result = $this->sut->execute( $this->input( 'email_new_order', array( 'subject' => 'New subject' ) ) );

		$this->assertSame( 'Old subject', $result['settings'][0]['previous'] );
		$this->assertSame( 'New subject', $result['settings'][0]['value'] );
		$this->assertSame(
			array(
				'subject'   => 'New subject',
				'recipient' => 'private@example.test',
			),
			get_option( 'woocommerce_new_order_settings' )
		);
	}

	/**
	 * @testdox Should keep backslashes in text values, whether saved through the admin save or as an option member.
	 */
	public function test_keeps_backslashes(): void {
		$this->add_general_field( array() );
		$this->add_general_field(
			array(
				'id'   => 'extension_textarea',
				'type' => 'textarea',
			)
		);

		$result = $this->sut->execute(
			$this->input(
				'general',
				array(
					'extension_field'    => 'C:\Reports',
					'extension_textarea' => 'C:\Reports\\\\Archive',
				)
			)
		);

		$this->assertIsArray( $result );
		$this->assertSame( 'C:\Reports', get_option( 'extension_field' ) );
		$this->assertSame( 'C:\Reports\\\\Archive', get_option( 'extension_textarea' ) );
		$this->assertSame( 'C:\Reports', $result['settings'][0]['value'] );

		$this->sut->execute( $this->input( 'email_new_order', array( 'subject' => 'C:\Reports' ) ) );

		$this->assertSame( 'C:\Reports', get_option( 'woocommerce_new_order_settings' )['subject'] );
	}

	/**
	 * @testdox Should refuse an email recipient, which is not opted in to writes.
	 */
	public function test_refuses_email_recipient(): void {
		$result = $this->sut->execute( $this->input( 'email_new_order', array( 'recipient' => 'attacker@example.test' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_not_writable', $result->get_error_code() );
	}

	/**
	 * @testdox Should return the group error for unknown groups.
	 */
	public function test_unknown_group(): void {
		$result = $this->sut->execute( $this->input( 'missing', array( 'id' => 'value' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_group_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse customers without changing the setting.
	 */
	public function test_refuses_customer(): void {
		update_option( 'woocommerce_currency', 'USD' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );

		$result = $this->sut->execute( $this->input( 'general', array( 'woocommerce_currency' => 'EUR' ) ) );

		$this->assertWPError( $result );
		$this->assertSame( 'USD', get_option( 'woocommerce_currency' ) );
	}

	/**
	 * @testdox Should leave the settings query output unchanged by write opt-ins.
	 */
	public function test_settings_query_output_is_unchanged(): void {
		update_option( 'woocommerce_currency', 'GBP' );
		$this->add_general_field( array( 'title' => 'Extension field' ) );
		update_option( 'extension_field', 'stored' );

		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'general',
				'include' => array( 'extension_field', 'woocommerce_currency' ),
			)
		);

		$this->assertSame( 2, $result['total'] );
		$this->assertSame( array(), $result['omitted'] );
		$settings = array_column( $result['settings'], null, 'id' );
		$this->assertSame(
			array(
				'id'          => 'extension_field',
				'label'       => 'Extension field',
				'description' => '',
				'type'        => 'text',
				'value'       => 'stored',
				'default'     => '',
			),
			$settings['extension_field']
		);
		$this->assertSame( array( 'id', 'label', 'description', 'type', 'value', 'default', 'options' ), array_keys( $settings['woocommerce_currency'] ) );
		$this->assertSame( 'GBP', $settings['woocommerce_currency']['value'] );
	}
}
