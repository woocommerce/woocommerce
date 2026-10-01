<?php
/**
 * RegistrationContractsTest class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Tests\Internal\Abilities;

use Automattic\WooCommerce\Abilities\AbilityContracts;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityFields;
use Automattic\WooCommerce\Internal\AbilitiesApi\AbilityObjectValidators;

/**
 * A plugin ability opts in to in-memory writes and extension fields with
 * plain wp_register_ability() arguments.
 */
class RegistrationContractsTest extends \WC_Unit_Test_Case {

	private const WRITE = 'test-plugin/update-record';

	private const READ = 'test-plugin/records-query';

	/**
	 * Original action counts restored in tearDown.
	 *
	 * @var array<string, int|null>
	 */
	private $original_action_counts = array();

	/**
	 * Values passed to the extension field's update callback.
	 *
	 * @var array<int, mixed>
	 */
	private $updated = array();

	/**
	 * Set up test fixtures.
	 */
	public function setUp(): void {
		global $wp_actions;

		parent::setUp();

		foreach ( array( 'init', 'wp_abilities_api_init', 'wp_abilities_api_categories_init' ) as $action ) {
			$this->original_action_counts[ $action ] = $wp_actions[ $action ] ?? null;
		}

		if ( ! function_exists( 'wp_register_ability' ) ) {
			require_once WC_ABSPATH . 'vendor/wordpress/abilities-api/includes/bootstrap.php';
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

		$this->reset_registries();
		wc_register_ability_field(
			'test_record',
			'test_notes',
			array(
				'schema'            => array(
					'type'        => 'string',
					'description' => 'Note the notes plugin keeps on a record.',
				),
				'get_callback'      => static function ( TestRecord $record ) {
					return $record->note;
				},
				'update_callback'   => function ( $value, TestRecord $record ) {
					$this->updated[] = $value;
					$record->note    = $value;
				},
				'validate_callback' => static function ( $value ) {
					return 'reject' === $value ? new \WP_Error( 'test_note_rejected', 'Note rejected.' ) : true;
				},
			)
		);
		wc_register_ability_object_validator(
			'test_record',
			static function ( TestRecord $record ) {
				return 'Blocked' === $record->title ? new \WP_Error( 'test_blocked', 'Blocked by validator.' ) : true;
			}
		);

		TestRecord::$saves = 0;
		( new TestRecord( 7, 'Original', 'first' ) )->save();
		( new TestRecord( 8, 'Second' ) )->save();
		TestRecord::$saves = 0;
	}

	/**
	 * Tear down test fixtures.
	 */
	public function tearDown(): void {
		global $wp_actions;

		$this->unregister_abilities();
		$this->reset_registries();
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', 'no' );

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
	 * @testdox Should add the extension fields to the input and output schemas, including list outputs.
	 */
	public function test_schemas_gain_extensions(): void {
		$this->register( true );

		$field = array(
			'type'        => 'string',
			'description' => 'Note the notes plugin keeps on a record.',
		);
		$write = wp_get_ability( self::WRITE );
		$read  = wp_get_ability( self::READ );

		$this->assertSame( $field, $write->get_input_schema()['properties']['extensions']['properties']['test_notes'] );
		$this->assertSame( $field, $write->get_output_schema()['properties']['record']['properties']['extensions']['properties']['test_notes'] );
		$this->assertSame( $field, $read->get_output_schema()['properties']['records']['items']['properties']['extensions']['properties']['test_notes'] );
		$this->assertArrayNotHasKey( 'extensions', $read->get_input_schema()['properties'] );
	}

	/**
	 * @testdox Should apply the extension field before the single save and return it in the output.
	 */
	public function test_write_applies_field_saves_once_and_responds(): void {
		$this->register( true );

		$result = wp_get_ability( self::WRITE )->execute(
			array(
				'id'         => 7,
				'title'      => 'Renamed',
				'extensions' => array( 'test_notes' => 'second' ),
			)
		);

		$this->assertSame(
			array(
				'record' => array(
					'id'         => 7,
					'title'      => 'Renamed',
					'extensions' => array( 'test_notes' => 'second' ),
				),
			),
			$result
		);
		$this->assertSame( 1, TestRecord::$saves );
		$this->assertSame( 'second', TestRecord::load( 7 )->note );
		$this->assertSame( 'Renamed', TestRecord::load( 7 )->title );
	}

	/**
	 * @testdox Should save nothing when an object validator rejects the applied record.
	 */
	public function test_object_validator_rejection_saves_nothing(): void {
		$this->register( true );

		$result = wp_get_ability( self::WRITE )->execute(
			array(
				'id'         => 7,
				'title'      => 'Blocked',
				'extensions' => array( 'test_notes' => 'second' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		$this->assertSame( 'Blocked by validator.', $result->get_error_message() );
		$this->assertSame( 400, $result->get_error_data()['status'] );
		$this->assertSame( 0, TestRecord::$saves );
		$this->assertSame( 'Original', TestRecord::load( 7 )->title );
	}

	/**
	 * @testdox Should neither apply nor save when the extension field refuses its value.
	 */
	public function test_field_rejection_applies_and_saves_nothing(): void {
		$this->register( true );

		$result = wp_get_ability( self::WRITE )->execute(
			array(
				'id'         => 7,
				'title'      => 'Renamed',
				'extensions' => array( 'test_notes' => 'reject' ),
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( 'woocommerce_in_memory_write_rejected', $result->get_error_code() );
		$this->assertSame( 'Note rejected.', $result->get_error_message() );
		$this->assertEmpty( $this->updated );
		$this->assertSame( 0, TestRecord::$saves );
	}

	/**
	 * @testdox Should return the step's error and save nothing when validate refuses, apply fails or the record is missing.
	 *
	 * @testWith [7, "", "test_empty_title", 400]
	 *           [7, "throw", "woocommerce_in_memory_write_save_failed", 500]
	 *           [99, "Renamed", "woocommerce_in_memory_write_not_found", 404]
	 *
	 * @param int    $id     Record ID.
	 * @param string $title  Title input.
	 * @param string $code   Expected error code.
	 * @param int    $status Expected status.
	 */
	public function test_step_errors_save_nothing( int $id, string $title, string $code, int $status ): void {
		$this->register( true );

		$result = wp_get_ability( self::WRITE )->execute(
			array(
				'id'    => $id,
				'title' => $title,
			)
		);

		$this->assertWPError( $result );
		$this->assertSame( $code, $result->get_error_code() );
		$this->assertSame( $status, $result->get_error_data()['status'] );
		$this->assertSame( 0, TestRecord::$saves );
		$this->assertSame( 'Original', TestRecord::load( 7 )->title );
	}

	/**
	 * @testdox Should read the extension fields of every record a read ability lists.
	 */
	public function test_read_fills_extensions_for_each_item(): void {
		$this->register( true );

		$result = wp_get_ability( self::READ )->execute( array() );

		$this->assertSame(
			array(
				array(
					'id'         => 7,
					'title'      => 'Original',
					'extensions' => array( 'test_notes' => 'first' ),
				),
				array(
					'id'         => 8,
					'title'      => 'Second',
					'extensions' => array( 'test_notes' => '' ),
				),
			),
			$result['records']
		);
	}

	/**
	 * @testdox Should ignore the registration keys when the feature is off.
	 */
	public function test_feature_off_ignores_keys(): void {
		$this->register( false );

		$write = wp_get_ability( self::WRITE );
		$read  = wp_get_ability( self::READ );

		$this->assertArrayNotHasKey( 'extensions', $write->get_input_schema()['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $write->get_output_schema()['properties']['record']['properties'] );
		$this->assertArrayNotHasKey( 'extensions', $read->get_output_schema()['properties']['records']['items']['properties'] );
		$this->assertSame(
			array( 'own_execute' => true ),
			$write->execute(
				array(
					'id'    => 7,
					'title' => 'Renamed',
				)
			)
		);
		$this->assertSame(
			array(
				array(
					'id'    => 7,
					'title' => 'Original',
				),
				array(
					'id'    => 8,
					'title' => 'Second',
				),
			),
			$read->execute( array() )['records']
		);
		$this->assertSame( 0, TestRecord::$saves );
	}

	/**
	 * Register the plugin's abilities with plain wp_register_ability() calls.
	 *
	 * @param bool $enabled Whether the feature is enabled.
	 */
	private function register( bool $enabled ): void {
		update_option( 'woocommerce_feature_' . AbilityContracts::FEATURE_ID . '_enabled', $enabled ? 'yes' : 'no' );

		$record_schema = array(
			'type'       => 'object',
			'properties' => array(
				'id'    => array( 'type' => 'integer' ),
				'title' => array( 'type' => 'string' ),
			),
		);

		$callback = static function () use ( $record_schema ) {
			wp_register_ability(
				self::WRITE,
				array(
					'label'               => 'Update record',
					'description'         => 'Rename a record.',
					'category'            => 'test-plugin',
					'input_schema'        => array(
						'type'                 => 'object',
						'properties'           => array(
							'id'    => array( 'type' => 'integer' ),
							'title' => array( 'type' => 'string' ),
						),
						'required'             => array( 'id', 'title' ),
						'additionalProperties' => false,
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array( 'record' => $record_schema ),
					),
					'execute_callback'    => static function (): array {
						return array( 'own_execute' => true );
					},
					'permission_callback' => '__return_true',
					'in_memory_write'     => array(
						'object_type' => 'test_record',
						'subject'     => static function ( array $input ) {
							return TestRecord::load( $input['id'] );
						},
						'validate'    => static function ( TestRecord $record, array $input ) {
							return '' === $input['title'] ? new \WP_Error( 'test_empty_title', 'Title is empty.' ) : true;
						},
						'apply'       => static function ( TestRecord $record, array $input ) {
							if ( 'throw' === $input['title'] ) {
								throw new \RuntimeException( 'Apply failed.' );
							}
							$record->title = $input['title'];
						},
						'respond'     => static function ( TestRecord $record ): array {
							return array(
								'record' => array(
									'id'    => $record->id,
									'title' => $record->title,
								),
							);
						},
					),
					'extension_fields'    => array(
						'object_type' => 'test_record',
						'output'      => 'record',
					),
				)
			);

			wp_register_ability(
				self::READ,
				array(
					'label'               => 'Query records',
					'description'         => 'List records.',
					'category'            => 'test-plugin',
					'input_schema'        => array(
						'type'       => 'object',
						'properties' => array(),
						'default'    => array(),
					),
					'output_schema'       => array(
						'type'       => 'object',
						'properties' => array(
							'records' => array(
								'type'  => 'array',
								'items' => $record_schema,
							),
						),
					),
					'execute_callback'    => static function (): array {
						$records = array();
						foreach ( array( 7, 8 ) as $id ) {
							$record    = TestRecord::load( $id );
							$records[] = array(
								'id'    => $record->id,
								'title' => $record->title,
							);
						}
						return array( 'records' => $records );
					},
					'permission_callback' => '__return_true',
					'meta'                => array( 'annotations' => array( 'readonly' => true ) ),
					'extension_fields'    => array(
						'object_type' => 'test_record',
						'output'      => 'records',
						'load'        => array( TestRecord::class, 'load' ),
					),
				)
			);
		};

		$this->unregister_abilities();
		add_action( 'wp_abilities_api_init', $callback );
		do_action( 'wp_abilities_api_init' );
		remove_action( 'wp_abilities_api_init', $callback );
	}

	/**
	 * Unregister the plugin's abilities.
	 */
	private function unregister_abilities(): void {
		foreach ( array( self::WRITE, self::READ ) as $ability_id ) {
			if ( wp_has_ability( $ability_id ) ) {
				wp_unregister_ability( $ability_id );
			}
		}
	}

	/**
	 * Drop the fields and validators the test registered.
	 */
	private function reset_registries(): void {
		foreach ( array(
			AbilityFields::class           => 'fields',
			AbilityObjectValidators::class => 'validators',
		) as $class_name => $property ) {
			$reflection = new \ReflectionProperty( $class_name, $property );
			$reflection->setAccessible( true );
			$reflection->setValue( null, array() );
		}
	}
}
