<?php
/**
 * Settings query ability tests.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities\Domain;

use Automattic\WooCommerce\Internal\Abilities\AbilitiesLoader;

/**
 * Tests settings discovery and safe value reads.
 */
class SettingsQueryTest extends \WC_Unit_Test_Case {

	/**
	 * Register abilities and authenticate a manager.
	 */
	public function setUp(): void {
		parent::setUp();
		add_action( 'wp_abilities_api_init', array( AbilitiesLoader::class, 'register_abilities' ) );
		do_action( 'wp_abilities_api_init' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'shop_manager' ) ) );
	}

	/**
	 * @testdox Should discover groups without input and paginate by string IDs.
	 */
	public function test_groups(): void {
		$ability = wp_get_ability( 'woocommerce/settings-groups-query' );
		$result  = $ability->execute();
		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['total'] );
		$result = $ability->execute(
			array(
				'include'  => array( 'general' ),
				'per_page' => 1,
			)
		);
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 'general', $result['groups'][0]['id'] );
		$this->assertSame( 1, $result['total_pages'] );
	}

	/**
	 * @testdox Should read a single setting and count filtered results before paging.
	 */
	public function test_settings(): void {
		update_option( 'woocommerce_currency', 'GBP' );
		$ability = wp_get_ability( 'woocommerce/settings-query' );
		$result  = $ability->execute(
			array(
				'group'   => 'general',
				'include' => array( 'woocommerce_currency' ),
			)
		);
		$this->assertIsArray( $result );
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( 'GBP', $result['settings'][0]['value'] );
		$this->assertSame( 'USD', $result['settings'][0]['default'] );
		$this->assertArrayHasKey( 'GBP', (array) $result['settings'][0]['options'] );
		$this->assertSame( 'woocommerce_currency', $result['settings'][0]['id'] );
		$result = $ability->execute(
			array(
				'group'    => 'general',
				'per_page' => 1,
				'page'     => 2,
			)
		);
		$this->assertCount( 1, $result['settings'] );
		$this->assertGreaterThan( 1, $result['total'] );
		$this->assertSame( 2, $result['page'] );
	}

	/**
	 * @testdox Should omit password and unapproved text fields in both Core and extension groups.
	 * @testWith ["general"]
	 *           ["extension"]
	 *           ["email_new_order"]
	 * @param string $group Settings group.
	 */
	public function test_secret_fields( string $group ): void {
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
			'woocommerce_settings-' . $group,
			static function ( $settings ) {
				$settings[] = array(
					'id'         => 'api_key',
					'type'       => 'text',
					'option_key' => 'test_secret',
					'default'    => 'secret-sentinel',
				);
				$settings[] = array(
					'abilities'  => array( 'read' => true ),
					'id'         => 'password',
					'type'       => 'password',
					'option_key' => 'test_secret',
					'default'    => 'secret-sentinel',
				);
				$settings[] = array(
					'abilities'  => array( 'read' => true ),
					'id'         => 'mode',
					'type'       => 'text',
					'option_key' => 'test_mode',
				);
				return $settings;
			}
		);
		update_option( 'test_secret', 'secret-sentinel' );
		update_option( 'test_mode', 'sandbox' );
		$ability = wp_get_ability( 'woocommerce/settings-query' );
		$result  = $ability->execute(
			array(
				'group'    => $group,
				'per_page' => 100,
			)
		);
		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( 'secret-sentinel', wp_json_encode( $result ) );
		$this->assertContains( 'mode', array_column( $result['settings'], 'id' ) );
		$result = $ability->execute(
			array(
				'group'   => $group,
				'include' => array( 'api_key', 'password' ),
			)
		);
		$this->assertSame( 0, $result['total'] );
	}

	/**
	 * @testdox Should let an extension opt a Core setting out at its definition.
	 */
	public function test_core_setting_can_opt_out(): void {
		add_filter(
			'woocommerce_general_settings',
			static function ( $settings ) {
				foreach ( $settings as &$setting ) {
					if ( 'woocommerce_currency' === $setting['id'] ) {
						$setting['abilities']['read'] = false;
					}
				}
				return $settings;
			},
			20
		);
		update_option( 'test_secret', 'secret-sentinel' );
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'general',
				'include' => array( 'woocommerce_currency' ),
			)
		);
		$this->assertSame( 0, $result['total'] );
	}

	/**
	 * @testdox Should only expose a field when its definition explicitly opts in with boolean true.
	 * @testWith [null, false]
	 *           [true, false]
	 *           [{}, false]
	 *           [{"read": "true"}, false]
	 *           [{"read": 1}, false]
	 *           [{"read": false}, false]
	 *           [{"read": true}, true]
	 * @param mixed $abilities Field metadata.
	 * @param bool  $expected Whether the field is readable.
	 */
	public function test_definition_opt_in( $abilities, bool $expected ): void {
		add_filter(
			'woocommerce_general_settings',
			static function ( $settings ) use ( $abilities ) {
				$settings[] = array(
					'id'        => 'extension_mode',
					'title'     => 'Extension mode',
					'type'      => 'text',
					'default'   => 'sandbox',
					'abilities' => $abilities,
				);
				return $settings;
			}
		);
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'general',
				'include' => array( 'extension_mode' ),
			)
		);
		$this->assertIsArray( $result );
		$this->assertSame( $expected ? 1 : 0, $result['total'] );
		if ( $expected ) {
			$this->assertSame( 'sandbox', $result['settings'][0]['value'] );
		}
	}

	/**
	 * @testdox Should use the same value precedence as the admin field without reading an overridden option.
	 * @testWith [{}, "stored", true]
	 *           [{"value": null}, "stored", true]
	 *           [{"value": "supplied"}, "supplied", false]
	 *           [{"value": ""}, "", false]
	 *           [{"value": false}, false, false]
	 *           [{"value": 0}, 0, false]
	 *           [{"value": "supplied", "fixed_value": "fixed"}, "fixed", false]
	 *           [{"value": "supplied", "fixed_value": null}, "supplied", false]
	 *           [{"fixed_value": false}, false, false]
	 * @param array $metadata Value metadata.
	 * @param mixed $expected Expected value.
	 * @param bool  $reads_option Whether the option should be read.
	 */
	public function test_definition_values( array $metadata, $expected, bool $reads_option ): void {
		update_option( 'extension_value', 'stored' );
		$reads = 0;
		add_filter(
			'pre_option_extension_value',
			static function ( $value ) use ( &$reads ) {
				++$reads;
				return $value;
			}
		);
		add_filter(
			'woocommerce_general_settings',
			static function ( $settings ) use ( $metadata ) {
				$settings[] = $metadata + array(
					'id'        => 'extension_value',
					'type'      => 'text',
					'abilities' => array( 'read' => true ),
				);
				return $settings;
			}
		);
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'general',
				'include' => array( 'extension_value' ),
			)
		);
		$this->assertSame( 1, $result['total'] );
		$this->assertSame( $expected, $result['settings'][0]['value'] );
		$this->assertSame( $reads_option ? 1 : 0, $reads );

		$rest_settings = ( new \WC_REST_Setting_Options_Controller() )->get_group_settings( 'general' );
		$rest_setting  = array_column( $rest_settings, null, 'id' )['extension_value'];
		$this->assertSame( 'stored', $rest_setting['value'] );
		$this->assertArrayNotHasKey( 'fixed_value', $rest_setting );
		$this->assertArrayNotHasKey( 'abilities', $rest_setting );
	}

	/**
	 * @testdox Should read additional tax classes from their definition rather than a missing option.
	 */
	public function test_additional_tax_classes(): void {
		update_option( 'woocommerce_calc_taxes', 'yes' );
		delete_option( 'woocommerce_tax_classes' );
		\WC_Tax::create_tax_class( 'Ability fixture' );
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'tax',
				'include' => array( 'woocommerce_tax_classes' ),
			)
		);
		$this->assertSame( 1, $result['total'] );
		$this->assertStringContainsString( 'Ability fixture', $result['settings'][0]['value'] );
		$this->assertSame( implode( "\n", \WC_Tax::get_tax_classes() ), $result['settings'][0]['value'] );
	}

	/**
	 * @testdox Should still exclude unsafe fields and shapes when values are supplied by definitions.
	 * @testWith ["password", true, "secret"]
	 *           ["text", false, "secret"]
	 *           ["text", true, {"api_key": "secret"}]
	 * @param string $type Field type.
	 * @param bool   $read Read opt-in.
	 * @param mixed  $value Supplied value.
	 */
	public function test_supplied_values_do_not_bypass_exclusions( string $type, bool $read, $value ): void {
		add_filter(
			'woocommerce_general_settings',
			static function ( $settings ) use ( $type, $read, $value ) {
				$settings[] = array(
					'id'          => 'extension_secret',
					'type'        => $type,
					'abilities'   => array( 'read' => $read ),
					'value'       => $value,
					'fixed_value' => $value,
				);
				return $settings;
			}
		);
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'general',
				'include' => array( 'extension_secret' ),
			)
		);
		$this->assertSame( 0, $result['total'] );
	}

	/**
	 * @testdox Should omit unexpected associative values that could contain nested secrets.
	 */
	public function test_unexpected_value_shape(): void {
		update_option( 'woocommerce_currency', array( 'api_key' => 'secret-sentinel' ) );
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute(
			array(
				'group'   => 'general',
				'include' => array( 'woocommerce_currency' ),
			)
		);
		$this->assertIsArray( $result );
		$this->assertStringNotContainsString( 'secret-sentinel', wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should read approved email fields without returning adjacent credentials or recipients.
	 */
	public function test_email_option_members(): void {
		update_option(
			'woocommerce_new_order_settings',
			array(
				'enabled'   => 'yes',
				'api_key'   => 'secret-sentinel',
				'recipient' => 'private@example.test',
			)
		);
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute( array( 'group' => 'email_new_order' ) );
		$this->assertIsArray( $result );
		$this->assertContains( 'enabled', array_column( $result['settings'], 'id' ) );
		$this->assertStringNotContainsString( 'secret-sentinel', wp_json_encode( $result ) );
		$this->assertStringNotContainsString( 'private@example.test', wp_json_encode( $result ) );
	}

	/**
	 * @testdox Should return a stable error for unknown groups.
	 */
	public function test_unknown_group(): void {
		$result = wp_get_ability( 'woocommerce/settings-query' )->execute( array( 'group' => 'missing' ) );
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_ability_settings_group_not_found', $result->get_error_code() );
	}

	/**
	 * @testdox Should refuse callers without settings access.
	 * @testWith ["woocommerce/settings-query", {"group":"general"}]
	 *           ["woocommerce/settings-groups-query", {}]
	 * @param string $name Ability name.
	 * @param array  $input Input.
	 */
	public function test_permissions( string $name, array $input ): void {
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'customer' ) ) );
		$this->assertWPError( wp_get_ability( $name )->execute( $input ) );
	}
}
