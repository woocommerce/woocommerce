<?php
declare(strict_types=1);

namespace Automattic\WooCommerce\Blocks\SharedStores;

/**
 * Names the product scopes that hold a `productId`, `variation` and
 * `scopeName` together in the `woocommerce` Interactivity API namespace.
 *
 * A name is a readable prefix plus `wp_unique_id_from_values()` over the
 * values that identify the thing being named, with an occurrence number
 * appended only from the second time those exact values are seen onward.
 * *Places* — the scope elements whose inner blocks establish where a form
 * renders — are tracked as a stack, so the current place is always the
 * innermost one still open.
 *
 * This is an experimental API and may change in future versions.
 */
final class ProductScopes {

	/**
	 * Prefix for a place's name.
	 */
	private const PLACE_PREFIX = 'wc-place-';

	/**
	 * Prefix for a scope element's name when it is not a place.
	 */
	private const ITEM_PREFIX = 'wc-item-';

	/**
	 * Prefix for a form's name.
	 */
	private const FORM_PREFIX = 'wc-form-';

	/**
	 * How many times each base name has been produced this request.
	 *
	 * @var array<string, int>
	 */
	private static array $occurrence_counts = array();

	/**
	 * Names of the places currently open, innermost last.
	 *
	 * @var string[]
	 */
	private static array $open_places = array();

	/**
	 * The name of the form currently rendering, if any.
	 *
	 * @var string
	 */
	private static string $current_form_name = '';

	/**
	 * Turn identifying values into a name, appending an occurrence number
	 * from the second time those exact values are seen onward.
	 *
	 * @param array  $values Values that identify the thing being named.
	 * @param string $prefix Readable prefix for the generated name.
	 * @return array The name and its occurrence number, as `array( $name, $occurrence )`.
	 * @phpstan-param non-empty-array $values
	 */
	private static function name_from_values( array $values, string $prefix ): array {
		$base = wp_unique_id_from_values( $values, $prefix );

		if ( ! isset( self::$occurrence_counts[ $base ] ) ) {
			self::$occurrence_counts[ $base ] = 0;
		}

		++self::$occurrence_counts[ $base ];
		$occurrence = self::$occurrence_counts[ $base ];
		$name       = 1 === $occurrence ? $base : $base . '-' . $occurrence;

		return array( $name, $occurrence );
	}

	/**
	 * Enter a place-making scope element before its inner blocks render.
	 *
	 * @since 11.3.0
	 *
	 * @param array $values Values that identify the place: at minimum the
	 *                      element's product id and the name of the place
	 *                      it is entered in (empty at page level).
	 * @return string The place's name.
	 * @phpstan-param non-empty-array $values
	 */
	public static function enter_place( array $values ): string {
		list( $name ) = self::name_from_values( $values, self::PLACE_PREFIX );

		self::$open_places[] = $name;

		return $name;
	}

	/**
	 * Leave the current place after its inner blocks have rendered.
	 *
	 * @since 11.3.0
	 *
	 * @return string The name entering this place returned, or '' when no
	 *                place was open.
	 */
	public static function leave_place(): string {
		if ( empty( self::$open_places ) ) {
			wc_doing_it_wrong( __FUNCTION__, __( 'leave_place() was called with no place open.', 'woocommerce' ), '11.3.0' );
			return '';
		}

		return array_pop( self::$open_places );
	}

	/**
	 * The name of the innermost open place.
	 *
	 * @since 11.3.0
	 *
	 * @return string The current place's name, or '' when none is open.
	 */
	public static function current_place(): string {
		return empty( self::$open_places ) ? '' : self::$open_places[ count( self::$open_places ) - 1 ];
	}

	/**
	 * Name a scope element that is not a place, such as a legacy Products
	 * item.
	 *
	 * @since 11.3.0
	 *
	 * @param array $values Values that identify the element.
	 * @return string The element's name.
	 * @phpstan-param non-empty-array $values
	 */
	public static function name_scope_element( array $values ): string {
		list( $name ) = self::name_from_values( $values, self::ITEM_PREFIX );

		return $name;
	}

	/**
	 * Name a form and tell it whether it declares a scope of its own.
	 *
	 * Occurrence 1 of the form's key means no form for that product has
	 * rendered earlier in that place, so the form declares nothing and
	 * shares its place's scope; from occurrence 2 on it declares its own.
	 *
	 * @since 11.3.0
	 *
	 * @param array $values Values that identify the form: the name of its
	 *                      place (empty at page level) and its product id.
	 * @return array The form's name and whether it declares a scope of its
	 *               own, as `array( 'name' => string, 'declares' => bool )`.
	 * @phpstan-param non-empty-array $values
	 */
	public static function name_form( array $values ): array {
		list( $name, $occurrence ) = self::name_from_values( $values, self::FORM_PREFIX );

		return array(
			'name'     => $name,
			'declares' => $occurrence > 1,
		);
	}

	/**
	 * Derive a grouped child row's name from its form's name.
	 *
	 * Stateless, so a form can list its children's names for its own
	 * context and each child row can name itself the same way.
	 *
	 * @since 11.3.0
	 *
	 * @param string $form_name        The enclosing form's name.
	 * @param int    $child_product_id The child row's product id.
	 * @return string The child row's name.
	 */
	public static function get_grouped_child_scope_name( string $form_name, int $child_product_id ): string {
		return $form_name . ':' . $child_product_id;
	}

	/**
	 * Build the `woocommerce` context array a scope element declares.
	 *
	 * @since 11.3.0
	 *
	 * @param int    $product_id The scope element's product id.
	 * @param array  $variation  The initial variation, as a list of
	 *                           `{ attribute, value }` entries; empty when
	 *                           there is none.
	 * @param string $scope_name The scope element's name.
	 * @return array The context array, holding `productId`, `variation`
	 *               and `scopeName` as its only keys.
	 */
	public static function get_scope_context( int $product_id, array $variation, string $scope_name ): array {
		return array(
			'productId' => $product_id,
			'variation' => $variation,
			'scopeName' => $scope_name,
		);
	}

	/**
	 * The `{ attribute, value }` entries a product contributes to a scope
	 * element's declared `variation`: its own variation attributes when it
	 * is a variation, empty for anything else. Loads the product with
	 * `wc_get_product()`; pass an id whose product is already loaded on
	 * this request.
	 *
	 * @since 11.3.0
	 *
	 * @param int $product_id The product id.
	 * @return array The `{ attribute, value }` entries, empty when the
	 *               product is not a variation.
	 */
	public static function get_scope_variation( int $product_id ): array {
		$product = wc_get_product( $product_id );

		if ( ! $product instanceof \WC_Product_Variation ) {
			return array();
		}

		$variation_attributes = $product->get_variation_attributes();

		return array_map(
			static function ( $attribute, $value ) {
				return array(
					'attribute' => $attribute,
					'value'     => $value,
				);
			},
			array_keys( $variation_attributes ),
			$variation_attributes
		);
	}

	/**
	 * Sets the name of the form whose inner blocks are rendering, so they
	 * can read it back.
	 *
	 * @since 11.3.0
	 *
	 * @param string $name The form's name.
	 */
	public static function set_current_form_name( string $name ): void {
		self::$current_form_name = $name;
	}

	/**
	 * The name of the form currently rendering.
	 *
	 * @since 11.3.0
	 *
	 * @return string The current form's name, or '' when none is set.
	 */
	public static function get_current_form_name(): string {
		return self::$current_form_name;
	}

	/**
	 * Clear the name of the form currently rendering.
	 *
	 * @since 11.3.0
	 */
	public static function clear_current_form_name(): void {
		self::$current_form_name = '';
	}

	/**
	 * Reset the helper's per-request state: occurrence counts, open places
	 * and the current form name.
	 *
	 * @internal For tests.
	 *
	 * @since 11.3.0
	 */
	public static function reset(): void {
		self::$occurrence_counts = array();
		self::$open_places       = array();
		self::$current_form_name = '';
	}
}
