<?php
declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\ProductCustoms;

use WC_Product;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the customs fields in the classic product and variation editors.
 */
final class ClassicEditorFields {

	/**
	 * Outputs the customs fields for a product, or for a variation when a parent and loop index are given.
	 *
	 * @since 11.3.0
	 *
	 * @param WC_Product      $product        Product being edited.
	 * @param WC_Product|null $parent_product Parent product whose values a variation inherits.
	 * @param int|null        $loop           Variation index, or null for the parent product.
	 * @return void
	 */
	public function render_fields( WC_Product $product, ?WC_Product $parent_product = null, ?int $loop = null ): void {
		$countries      = WC()->countries->get_countries();
		$parent_code    = $parent_product ? $parent_product->get_customs_commodity_code( 'edit' ) : null;
		$parent_country = $parent_product ? $parent_product->get_customs_country_of_origin( 'edit' ) : null;
		$parent_desc    = $parent_product ? $parent_product->get_customs_description( 'edit' ) : null;
		?>
		<div class="wc-product-customs <?php echo null !== $loop ? 'hide_if_variation_virtual' : ''; ?>">
			<h4><?php esc_html_e( 'Customs', 'woocommerce' ); ?></h4>
			<?php
			woocommerce_wp_text_input(
				$this->get_field_args( 'customs_commodity_code', $loop ) + array(
					'value'       => $product->get_customs_commodity_code( 'edit' ) ?? '',
					'label'       => __( 'Commodity code', 'woocommerce' ),
					'description' => $this->get_help_text( __( 'The HS (Harmonized System) code used to classify this product for customs. Enter 6–14 digits; spaces and punctuation are removed.', 'woocommerce' ), $parent_code ),
					'placeholder' => $parent_code ?? '',
				),
				$product
			);

			woocommerce_wp_select(
				$this->get_field_args( 'customs_country_of_origin', $loop ) + array(
					'value'       => $product->get_customs_country_of_origin( 'edit' ) ?? '',
					'label'       => __( 'Country of origin', 'woocommerce' ),
					'description' => $this->get_help_text( __( 'The country where this product was manufactured or produced.', 'woocommerce' ), null === $parent_country ? null : ( $countries[ $parent_country ] ?? $parent_country ) ),
					'options'     => array( '' => null !== $parent_country ? __( 'Same as parent', 'woocommerce' ) : __( 'Select a country', 'woocommerce' ) ) + $countries,
				),
				$product
			);

			woocommerce_wp_text_input(
				$this->get_field_args( 'customs_description', $loop ) + array(
					'value'             => $product->get_customs_description( 'edit' ) ?? '',
					'label'             => __( 'Customs description', 'woocommerce' ),
					'description'       => $this->get_help_text( __( 'A plain-text description for customs forms, up to 35 characters. Emoji and special symbols are not allowed.', 'woocommerce' ), $parent_desc ),
					'placeholder'       => $parent_desc ?? '',
					'custom_attributes' => array( 'maxlength' => 35 ),
				),
				$product
			);
			?>
		</div>
		<?php
	}

	/**
	 * Field arguments that differ between the product form and a variation row.
	 *
	 * @param string   $property Customs prop name.
	 * @param int|null $loop     Variation index, or null for the parent product.
	 * @return array
	 */
	private function get_field_args( string $property, ?int $loop ): array {
		if ( null === $loop ) {
			return array(
				'id'       => '_' . $property,
				'name'     => '_' . $property,
				'desc_tip' => true,
			);
		}

		return array(
			'id'            => 'variable_' . $property . $loop,
			'name'          => 'variable_' . $property . '[' . $loop . ']',
			'wrapper_class' => 'form-row form-row-full',
			'desc_tip'      => true,
		);
	}

	/**
	 * Appends the inherited parent value to a field's help text, when there is one.
	 *
	 * @param string      $description Field help text.
	 * @param string|null $inherited   Parent value shown to the merchant, or null.
	 * @return string
	 */
	private function get_help_text( string $description, ?string $inherited ): string {
		if ( null === $inherited ) {
			return $description;
		}

		/* translators: %s: Customs value inherited from the parent product. */
		return $description . ' ' . sprintf( __( 'Leave blank to inherit from the product: %s.', 'woocommerce' ), $inherited );
	}
}
