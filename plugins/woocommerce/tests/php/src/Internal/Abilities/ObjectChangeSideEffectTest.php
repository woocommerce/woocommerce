<?php
/**
 * ObjectChangeSideEffectTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

/**
 * An object change ability runs change(), the extension field update callbacks
 * and the object validators with side effects blocked.
 */
class ObjectChangeSideEffectTest extends \WC_Unit_Test_Case {

	private const ABILITY = 'test-plugin/rename-product';

	/**
	 * Original action counts restored in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

	/**
	 * Product renamed by the tests.
	 *
	 * @var \WC_Product
	 */
	private $product;

	/**
	 * Emails that reached PHPMailer.
	 *
	 * @var int
	 */
	private $mails_sent = 0;

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}
		$wp_actions['init'] = max( 1, (int) ( $wp_actions['init'] ?? 0 ) ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited

		if ( ! wp_has_ability_category( 'test-plugin' ) ) {
			$callback = static function () {
				wp_register_ability_category(
					'test-plugin',
					array(
						'label'       => 'Test plugin',
						'description' => 'Abilities of a plugin that is not WooCommerce.',
					)
				);
			};
			add_action( 'wp_abilities_api_categories_init', $callback );
			do_action( 'wp_abilities_api_categories_init' );
			remove_action( 'wp_abilities_api_categories_init', $callback );
		}

		$callback = static function () {
			if ( wp_has_ability( self::ABILITY ) ) {
				return;
			}
			wp_register_ability(
				self::ABILITY,
				array(
					'label'               => 'Rename product',
					'description'         => 'Rename a product.',
					'category'            => 'test-plugin',
					'ability_class'       => TestSideEffectAbility::class,
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array( 'type' => 'integer' ),
							'name'        => array( 'type' => 'string' ),
							'side_effect' => array( 'type' => 'string' ),
							'extensions'  => array( 'type' => 'object' ),
						),
						'required'   => array( 'id', 'name' ),
					),
					'permission_callback' => '__return_true',
				)
			);
		};
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );

		add_action(
			'phpmailer_init',
			function () {
				++$this->mails_sent;
			}
		);

		$this->product = \WC_Helper_Product::create_simple_product( true, array( 'name' => 'Original' ) );
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		wp_unregister_ability( self::ABILITY );

		foreach ( $this->original_action_counts as $action => $original_count ) {
			if ( null !== $original_count ) {
				$wp_actions[ $action ] = $original_count; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			} else {
				unset( $wp_actions[ $action ] ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
			}
		}

		parent::tearDown();
	}

	/**
	 * @testdox Should refuse a change that saves, sends email or calls out over HTTP, and save nothing.
	 *
	 * @testWith ["save", "database write"]
	 *           ["mail", "email"]
	 *           ["http", "HTTP request"]
	 *
	 * @param string $side_effect Side effect change() causes.
	 * @param string $attempted   What the message must name.
	 */
	public function test_change_with_side_effect_is_refused( string $side_effect, string $attempted ): void {
		$result = $this->rename( 'Renamed', array( 'side_effect' => $side_effect ) );

		$this->assertSideEffectRefusal( $result, $attempted );
		$this->assertSame( 0, $this->mails_sent );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should refuse a change whose extension field update callback writes post meta directly.
	 */
	public function test_field_update_that_writes_is_refused(): void {
		add_filter(
			'woocommerce_ability_fields',
			static function ( array $fields ): array {
				$fields['test_leaky'] = array(
					'schema'          => array( 'type' => 'string' ),
					'update_callback' => static function ( $value, \WC_Product $product ) {
						update_post_meta( $product->get_id(), '_test_leaky', $value );
					},
				);
				return $fields;
			}
		);

		$result = $this->rename( 'Renamed', array( 'extensions' => array( 'test_leaky' => 'abc' ) ) );

		$this->assertSideEffectRefusal( $result, 'database write' );
		$this->assertSame( array(), get_post_meta( $this->product->get_id(), '_test_leaky', false ) );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should refuse a change whose object validator writes an option.
	 */
	public function test_validator_that_writes_is_refused(): void {
		add_filter(
			'woocommerce_ability_object_validators',
			static function ( array $validators ): array {
				$validators[] = static function () {
					update_option( 'test_side_effect_option', 'during' );
					return true;
				};
				return $validators;
			}
		);

		$result = $this->rename( 'Renamed' );

		$this->assertSideEffectRefusal( $result, 'database write' );
		$this->assertNull( $this->stored_option( 'test_side_effect_option' ) );
		$this->assertSame( 'Original', $this->stored_name() );
	}

	/**
	 * @testdox Should let a change write to WooCommerce's log table or set a transient, and save once.
	 *
	 * @testWith ["log"]
	 *           ["transient"]
	 *
	 * @param string $side_effect Allowed write change() makes.
	 */
	public function test_change_may_log_or_set_transient( string $side_effect ): void {
		$updates = did_action( 'woocommerce_update_product' );

		$result = $this->rename( 'Renamed', array( 'side_effect' => $side_effect ) );

		$this->assertSame(
			array(
				'id'   => $this->product->get_id(),
				'name' => 'Renamed',
			),
			$result
		);
		$this->assertSame( 'Renamed', $this->stored_name() );
		$this->assertSame( $updates + 1, did_action( 'woocommerce_update_product' ) );
	}

	/**
	 * @testdox Should lift every guard after the call, so a database write right after it works.
	 */
	public function test_guards_are_lifted_after_the_call(): void {
		$this->rename( 'Renamed', array( 'side_effect' => 'save' ) );

		update_option( 'test_side_effect_option', 'after' );

		$this->assertSame( 'after', $this->stored_option( 'test_side_effect_option' ) );
	}

	/**
	 * Run the ability.
	 *
	 * @param string $name  New name.
	 * @param array  $extra More input.
	 * @return mixed
	 */
	private function rename( string $name, array $extra = array() ) {
		return wp_get_ability( self::ABILITY )->execute(
			array_merge(
				array(
					'id'   => $this->product->get_id(),
					'name' => $name,
				),
				$extra
			)
		);
	}

	/**
	 * Assert the result is the side-effect refusal naming what was attempted.
	 *
	 * @param mixed  $result    Ability result.
	 * @param string $attempted What the message must name.
	 */
	private function assertSideEffectRefusal( $result, string $attempted ): void {
		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_side_effect', $result->get_error_code() );
		$this->assertStringContainsString( $attempted, $result->get_error_message() );
		$this->assertSame( 500, $result->get_error_data()['status'] );
	}

	/**
	 * The product name in the database.
	 */
	private function stored_name(): string {
		global $wpdb;
		return (string) $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM {$wpdb->posts} WHERE ID = %d", $this->product->get_id() ) );
	}

	/**
	 * An option value in the database, or null.
	 *
	 * @param string $name Option name.
	 * @return string|null
	 */
	private function stored_option( string $name ): ?string {
		global $wpdb;
		return $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM {$wpdb->options} WHERE option_name = %s", $name ) );
	}
}
