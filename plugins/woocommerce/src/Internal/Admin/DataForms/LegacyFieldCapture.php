<?php
/**
 * Legacy Field Capture
 *
 * Captures field definitions from WC helper functions during hook execution
 * without rendering HTML output.
 *
 * @package WooCommerce\Internal\Admin\DataForms
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Internal\Admin\DataForms;

use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Static utility class that captures field definitions from legacy WC helper functions.
 *
 * When capture mode is active, helper functions (woocommerce_wp_text_input, etc.)
 * push their field definition arrays to this collector instead of echoing HTML.
 *
 * @since 10.9.0
 */
class LegacyFieldCapture {

	/**
	 * Whether capture mode is active.
	 *
	 * @var bool
	 */
	private static bool $capturing = false;

	/**
	 * Collected field definitions grouped by hook name.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private static array $fields = array();

	/**
	 * The hook currently being captured.
	 *
	 * @var string
	 */
	private static string $current_hook = '';

	/**
	 * Allowlist of hooks that can be captured.
	 *
	 * @var string[]
	 */
	private const ALLOWED_HOOKS = array(
		'woocommerce_variation_options',
		'woocommerce_variation_options_pricing',
		'woocommerce_variation_options_inventory',
		'woocommerce_variation_options_dimensions',
		'woocommerce_variation_options_download',
		'woocommerce_product_after_variable_attributes',
	);

	/**
	 * Capture field definitions from the given hooks.
	 *
	 * Fires each hook in capture mode so that WC helper functions push their
	 * field definitions to the collector instead of rendering HTML.
	 *
	 * @since 10.9.0
	 *
	 * @param string[] $hooks Hook names to capture.
	 * @return array<string, array<int, array<string, mixed>>> Field definitions grouped by hook name.
	 */
	public static function capture( array $hooks ): array {
		self::ensure_helper_functions_loaded();
		self::reset();

		$mock_post     = new WP_Post( (object) array( 'ID' => 0 ) );
		$variation_data = array();

		foreach ( $hooks as $hook ) {
			if ( ! in_array( $hook, self::ALLOWED_HOOKS, true ) ) {
				continue;
			}

			self::$current_hook = $hook;
			self::$fields[ $hook ] = array();
			self::$capturing = true;

			try {
				ob_start();
				do_action( $hook, 0, $variation_data, $mock_post );
				ob_end_clean();
			} catch ( \Throwable $e ) {
				if ( ob_get_level() ) {
					ob_end_clean();
				}
				if ( function_exists( 'wc_get_logger' ) ) {
					wc_get_logger()->error(
						sprintf( 'LegacyFieldCapture: Exception during hook "%s": %s', $hook, $e->getMessage() ),
						array( 'source' => 'legacy-field-capture' )
					);
				}
			}

			self::$capturing = false;
		}

		self::$current_hook = '';
		$result = self::$fields;
		self::reset();

		return $result;
	}

	/**
	 * Whether capture mode is currently active.
	 *
	 * @since 10.9.0
	 *
	 * @return bool
	 */
	public static function is_capturing(): bool {
		return self::$capturing;
	}

	/**
	 * Collect a field definition from a helper function.
	 *
	 * Called by the modified WC helper functions when capture mode is active.
	 *
	 * @since 10.9.0
	 *
	 * @param string               $helper_type The helper function type (text_input, select, checkbox, etc.).
	 * @param array<string, mixed> $field       The raw field array passed to the helper function.
	 */
	public static function collect( string $helper_type, array $field ): void {
		if ( ! self::$capturing || empty( self::$current_hook ) ) {
			return;
		}

		$raw_id  = $field['id'] ?? '';
		$base_id = self::strip_loop_index( $raw_id );

		$definition = array(
			'id'                => $base_id,
			'type'              => $helper_type,
			'input_type'        => $field['type'] ?? 'text',
			'label'             => $field['label'] ?? '',
			'meta_key'          => $base_id,
			'placeholder'       => $field['placeholder'] ?? '',
			'description'       => is_array( $field['description'] ?? null )
				? implode( ' ', $field['description'] )
				: ( $field['description'] ?? '' ),
			'default_value'     => $field['value'] ?? '',
			'wrapper_class'     => $field['wrapper_class'] ?? '',
			'custom_attributes' => $field['custom_attributes'] ?? array(),
			'options'           => $field['options'] ?? array(),
			'hidden'            => 'hidden_input' === $helper_type,
		);

		self::$fields[ self::$current_hook ][] = $definition;
	}

	/**
	 * Reset the capture state.
	 *
	 * @since 10.9.0
	 */
	public static function reset(): void {
		self::$capturing    = false;
		self::$fields       = array();
		self::$current_hook = '';
	}

	/**
	 * Get the list of allowed hook names.
	 *
	 * @since 10.9.0
	 *
	 * @return string[]
	 */
	public static function get_allowed_hooks(): array {
		return self::ALLOWED_HOOKS;
	}

	/**
	 * Register meta_data REST field on the product post type.
	 *
	 * The WP REST API endpoint (/wp/v2/product) uses WP_REST_Posts_Controller
	 * which does not include WooCommerce's meta_data array. This ensures
	 * meta_data round-trips through the entity store.
	 *
	 * @since 10.9.0
	 */
	public static function register_meta_data_rest_field(): void {
		register_rest_field(
			'product',
			'meta_data',
			array(
				'get_callback'    => function ( $post_array ) {
					$product = wc_get_product( $post_array['id'] );
					if ( ! $product ) {
						return array();
					}

					return array_values(
						array_map(
							function ( $meta ) {
								return array(
									'id'    => $meta->id,
									'key'   => $meta->key,
									'value' => $meta->value,
								);
							},
							$product->get_meta_data()
						)
					);
				},
				'update_callback' => function ( $meta_data, $post ) {
					if ( ! is_array( $meta_data ) ) {
						return;
					}

					$product = wc_get_product( $post->ID );
					if ( ! $product ) {
						return;
					}

					foreach ( $meta_data as $meta ) {
						if ( empty( $meta['key'] ) ) {
							continue;
						}

						$product->update_meta_data(
							$meta['key'],
							$meta['value'] ?? '',
							! empty( $meta['id'] ) ? (int) $meta['id'] : 0
						);
					}

					$product->save_meta_data();
				},
				'schema'          => array(
					'description' => __( 'Meta data.', 'woocommerce' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'    => array(
								'description' => __( 'Meta ID.', 'woocommerce' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'key'   => array(
								'description' => __( 'Meta key.', 'woocommerce' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'value' => array(
								'description' => __( 'Meta value.', 'woocommerce' ),
								'type'        => 'mixed',
								'context'     => array( 'view', 'edit' ),
							),
						),
					),
				),
			)
		);
	}

	/**
	 * Strip loop index suffix from a field ID.
	 *
	 * Field IDs in variation loops often end with [0], [1], etc.
	 * This strips that suffix to get the base field ID.
	 *
	 * @param string $field_id The raw field ID.
	 * @return string The base field ID without loop index.
	 */
	private static function strip_loop_index( string $field_id ): string {
		return (string) preg_replace( '/\[\d+\]$/', '', $field_id );
	}

	/**
	 * Ensure WC meta box helper functions are loaded.
	 *
	 * These functions are admin-only and may not be available in REST context.
	 */
	private static function ensure_helper_functions_loaded(): void {
		if ( ! function_exists( 'woocommerce_wp_text_input' ) ) {
			require_once WC_ABSPATH . 'includes/admin/wc-meta-box-functions.php';
		}
	}
}
