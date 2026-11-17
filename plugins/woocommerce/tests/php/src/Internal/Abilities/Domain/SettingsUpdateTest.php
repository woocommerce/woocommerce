<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

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
	 * Builds an update input.
	 *
	 * @param string $group  Settings group.
	 * @param array  $values Setting IDs mapped to new values.
	 * @return array
	 */
	private function input( string $group, array $values ): array {
		$settings = array();
		foreach ( $values as $id => $value ) {
			$settings[] = array(
				'id'    => $id,
				'value' => $value,
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
		$this->assertSame( 'USD', get_option( 'woocommerce_currency' ) );
		$this->assertSame( 'Berlin', get_option( 'woocommerce_store_city' ) );
		$this->assertSame( 'DE', get_option( 'woocommerce_default_country' ) );
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
