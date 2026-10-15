<?php
/**
 * Ability product types class file.
 */

declare( strict_types=1 );

namespace Automattic\WooCommerce\Abilities;

use Automattic\WooCommerce\Enums\ProductStockStatus;
use Automattic\WooCommerce\Enums\ProductType;

defined( 'ABSPATH' ) || exit;

/**
 * The product types that the product abilities create, update and list.
 *
 * Each type is registered under an agent-facing alias, such as `physical` or
 * `digital`, that maps to a WooCommerce product type plus the Core fields the
 * type accepts. Core registers its own aliases here. An extension registers
 * the alias of its own product type inside the `woocommerce_ability_extensions_init`
 * action, which fires only when the `ability_extensions` feature is on. The
 * extension's own data arrives through the fields it registers with
 * `AbilityExtensions::register_field()`.
 *
 * The API is experimental while the `ability_extensions` feature exists.
 *
 * @since 11.4.0
 */
class AbilityProductTypes {

	/**
	 * Core fields that every type accepts.
	 */
	public const COMMON_FIELDS = array( 'name', 'sku', 'description', 'short_description', 'status' );

	/**
	 * Core fields that a type can list in `fields`, next to the common fields.
	 */
	public const OPTIONAL_FIELDS = array(
		'regular_price',
		'sale_price',
		'manage_stock',
		'stock_quantity',
		'stock_status',
		'external_url',
		'button_text',
		'grouped_products',
		'attributes',
		'variations',
	);

	/**
	 * Fields that only the Core variable type accepts, because Core builds and
	 * saves the variations of a `WC_Product_Variable` draft.
	 */
	private const VARIABLE_ONLY_FIELDS = array( 'attributes', 'variations' );

	/**
	 * Product properties that a type can force in `product_props`.
	 */
	private const PRODUCT_PROPS = array( 'virtual', 'downloadable', 'manage_stock', 'stock_quantity', 'stock_status' );

	/**
	 * Product properties that a type can match in `query_props`.
	 */
	private const QUERY_PROPS = array( 'virtual', 'downloadable' );

	/**
	 * Registered types keyed by alias.
	 *
	 * @var array<string, array<string, mixed>>
	 */
	private static array $types = array();

	/**
	 * Whether Core's own types are registered. They come first, so an
	 * extension cannot take a Core alias.
	 *
	 * @var bool
	 */
	private static bool $core_registered = false;

	/**
	 * Register a product type under an agent-facing alias.
	 *
	 * An extension calls it inside the `woocommerce_ability_extensions_init` action,
	 * after it added its type to `product_type_selector`. A call after that action
	 * is refused.
	 *
	 * @since 11.4.0
	 *
	 * @param array $args {
	 *     Type arguments.
	 *
	 *     @type string   $alias         Agent-facing name, such as `subscription`. It is the `product_type_alias`
	 *                                   value that agents send. Registering an alias twice is refused.
	 *     @type string   $product_type  WooCommerce product type slug. It must be a key of `wc_get_product_types()`,
	 *                                   and `wc_get_product_object()` must return its product class.
	 *     @type string   $description   One sentence that tells agents when to pick the alias.
	 *     @type string[] $fields        Core fields the type accepts next to the common fields `name`, `sku`,
	 *                                   `description`, `short_description` and `status`. One of
	 *                                   `regular_price`, `sale_price`, `manage_stock`, `stock_quantity`,
	 *                                   `stock_status`, `external_url`, `button_text` and `grouped_products`.
	 *     @type array    $product_props Optional. Properties that Core sets when it creates a product of the alias
	 *                                   or changes a product to it, keyed by `virtual`, `downloadable`,
	 *                                   `manage_stock`, `stock_quantity` or `stock_status`.
	 *     @type array    $query_props   Optional. Booleans keyed by `virtual` or `downloadable`. A product
	 *                                   belongs to the alias when its type matches and each one matches, and
	 *                                   `products-query` filters by them.
	 * }
	 */
	public static function register( array $args ): void {
		if ( did_action( 'woocommerce_ability_extensions_init' ) && ! doing_action( 'woocommerce_ability_extensions_init' ) ) {
			wc_doing_it_wrong( __METHOD__, 'Register a product type inside the woocommerce_ability_extensions_init action, before the abilities build their schemas.', '11.4.0' );
			return;
		}
		self::register_core_types();
		self::add( $args );
	}

	/**
	 * Check the arguments and store the type.
	 *
	 * @param array $args Type arguments.
	 * @return bool Whether the type was stored.
	 */
	private static function add( array $args ): bool {
		foreach ( array( 'alias', 'product_type' ) as $key ) {
			if ( ! is_string( $args[ $key ] ?? null ) || '' === $args[ $key ] || sanitize_key( $args[ $key ] ) !== $args[ $key ] ) {
				wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" argument must be a slug of lowercase letters, numbers, dashes and underscores.', $key ), '11.4.0' );
				return false;
			}
		}
		if ( isset( self::$types[ $args['alias'] ] ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" product type alias is already registered.', $args['alias'] ), '11.4.0' );
			return false;
		}
		if ( ! array_key_exists( $args['product_type'], wc_get_product_types() ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" product type is not registered. Add it with the product_type_selector filter first.', $args['product_type'] ), '11.4.0' );
			return false;
		}
		if ( ! is_string( $args['description'] ?? null ) || '' === trim( $args['description'] ) ) {
			wc_doing_it_wrong( __METHOD__, 'The "description" argument must be a sentence that tells agents when to pick the alias.', '11.4.0' );
			return false;
		}
		$fields  = is_array( $args['fields'] ?? null ) ? $args['fields'] : null;
		$unknown = null === $fields ? array() : array_diff( $fields, self::OPTIONAL_FIELDS );
		if ( null === $fields || ! empty( $unknown ) ) {
			wc_doing_it_wrong( __METHOD__, sprintf( 'The "fields" argument must be a list of these fields: %s.', implode( ', ', self::OPTIONAL_FIELDS ) ), '11.4.0' );
			return false;
		}
		if ( ProductType::VARIABLE !== $args['product_type'] && ! empty( array_intersect( $fields, self::VARIABLE_ONLY_FIELDS ) ) ) {
			wc_doing_it_wrong( __METHOD__, 'Only the variable product type accepts the "attributes" and "variations" fields.', '11.4.0' );
			return false;
		}
		$prop_keys = array(
			'product_props' => self::PRODUCT_PROPS,
			'query_props'   => self::QUERY_PROPS,
		);
		foreach ( $prop_keys as $key => $allowed ) {
			$props = $args[ $key ] ?? array();
			if ( ! is_array( $props ) || ! empty( array_diff( array_keys( $props ), $allowed ) ) ) {
				wc_doing_it_wrong( __METHOD__, sprintf( 'The "%s" argument must be an array keyed by: %s.', $key, implode( ', ', $allowed ) ), '11.4.0' );
				return false;
			}
		}

		self::$types[ $args['alias'] ] = array(
			'product_type'  => $args['product_type'],
			'description'   => $args['description'],
			'fields'        => array_values( array_unique( array_merge( self::COMMON_FIELDS, $fields ) ) ),
			'product_props' => $args['product_props'] ?? array(),
			'query_props'   => array_map( 'boolval', $args['query_props'] ?? array() ),
		);
		return true;
	}

	/**
	 * The registered types keyed by alias. Core's own types come first.
	 *
	 * @internal
	 *
	 * @return array<string, array{product_type: string, description: string, fields: string[], product_props: array<string, mixed>, query_props: array<string, bool>}>
	 */
	public static function get_all(): array {
		self::register_core_types();
		return self::$types;
	}

	/**
	 * The type registered under an alias.
	 *
	 * @internal
	 *
	 * @param string $alias Agent-facing alias.
	 * @return array|null
	 */
	public static function get( string $alias ): ?array {
		return self::get_all()[ $alias ] ?? null;
	}

	/**
	 * The alias of a product. The first alias with the product's type and
	 * matching `query_props` wins, then the first alias with the product's type.
	 *
	 * @internal
	 *
	 * @param \WC_Product $product Product.
	 * @return string|null
	 */
	public static function get_alias_for_product( \WC_Product $product ): ?string {
		$fallback = null;
		foreach ( self::get_all() as $alias => $type ) {
			if ( ! $product->is_type( $type['product_type'] ) ) {
				continue;
			}
			$fallback = $fallback ?? $alias;
			foreach ( $type['query_props'] as $prop => $value ) {
				if ( (bool) $product->{'get_' . $prop}() !== $value ) {
					continue 2;
				}
			}
			return $alias;
		}
		return $fallback;
	}

	/**
	 * Register Core's own aliases. They do not depend on the feature.
	 */
	private static function register_core_types(): void {
		if ( self::$core_registered ) {
			return;
		}
		self::$core_registered = true;

		$simple_fields = array( 'regular_price', 'sale_price', 'manage_stock', 'stock_quantity', 'stock_status' );
		$no_stock      = array(
			'manage_stock'   => false,
			'stock_quantity' => '',
		);

		self::add(
			array(
				'alias'         => 'physical',
				'product_type'  => ProductType::SIMPLE,
				'description'   => __( 'A simple product that is shipped and has no files to download.', 'woocommerce' ),
				'fields'        => $simple_fields,
				'product_props' => array(
					'virtual'      => false,
					'downloadable' => false,
				),
				'query_props'   => array(
					'virtual'      => false,
					'downloadable' => false,
				),
			)
		);
		self::add(
			array(
				'alias'         => 'virtual',
				'product_type'  => ProductType::SIMPLE,
				'description'   => __( 'A simple product that is not shipped and has no files to download, such as a service.', 'woocommerce' ),
				'fields'        => $simple_fields,
				'product_props' => array(
					'virtual'      => true,
					'downloadable' => false,
				),
				'query_props'   => array(
					'virtual'      => true,
					'downloadable' => false,
				),
			)
		);
		self::add(
			array(
				'alias'         => 'digital',
				'product_type'  => ProductType::SIMPLE,
				'description'   => __( 'A simple product that is not shipped and has files to download.', 'woocommerce' ),
				'fields'        => $simple_fields,
				'product_props' => array(
					'virtual'      => true,
					'downloadable' => true,
				),
				'query_props'   => array(
					'virtual'      => true,
					'downloadable' => true,
				),
			)
		);
		self::add(
			array(
				'alias'         => 'affiliate',
				'product_type'  => ProductType::EXTERNAL,
				'description'   => __( 'An external product that is bought on another site through a button.', 'woocommerce' ),
				'fields'        => array( 'regular_price', 'sale_price', 'external_url', 'button_text' ),
				'product_props' => $no_stock + array(
					'virtual'      => false,
					'downloadable' => false,
					'stock_status' => ProductStockStatus::IN_STOCK,
				),
			)
		);
		self::add(
			array(
				'alias'         => 'grouped',
				'product_type'  => ProductType::GROUPED,
				'description'   => __( 'A grouped product that lists other products as its children.', 'woocommerce' ),
				'fields'        => array( 'grouped_products' ),
				'product_props' => $no_stock,
			)
		);
		self::add(
			array(
				'alias'         => 'variable',
				'product_type'  => ProductType::VARIABLE,
				'description'   => __( 'A variable product with variations that each pick one option of every attribute.', 'woocommerce' ),
				'fields'        => array( 'attributes', 'variations' ),
				'product_props' => $no_stock,
			)
		);
	}
}
