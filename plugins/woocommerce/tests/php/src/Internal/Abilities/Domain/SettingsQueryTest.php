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
					'id'         => 'password',
					'type'       => 'password',
					'option_key' => 'test_secret',
					'default'    => 'secret-sentinel',
				);
				$settings[] = array(
					'id'         => 'mode',
					'type'       => 'text',
					'option_key' => 'test_mode',
				);
				return $settings;
			}
		);
		add_filter(
			'woocommerce_ability_settings_allowed_options',
			static function ( $options ) use ( $group ) {
				$options[ $group ]['mode']     = 'test_mode';
				$options[ $group ]['password'] = 'test_secret';
				return $options;
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
	 * @testdox Should not follow an extension's replacement option key for a Core setting.
	 */
	public function test_option_alias_is_not_trusted(): void {
		add_filter(
			'woocommerce_settings-general',
			static function ( $settings ) {
				foreach ( $settings as &$setting ) {
					if ( 'woocommerce_currency' === $setting['id'] ) {
						$setting['option_key'] = 'test_secret';
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
