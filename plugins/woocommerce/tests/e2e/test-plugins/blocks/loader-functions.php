<?php
/**
 * Plugin Name: WooCommerce Blocks Test Loader Functions
 * Description: Calls the global Interactivity API loader functions during render, for e2e testing.
 * Plugin URI: https://github.com/woocommerce/woocommerce
 * Author: WooCommerce
 *
 * @package woocommerce-blocks-test-loader-functions
 */

declare( strict_types = 1 );

add_shortcode(
	'wc_iapi_loader_functions_test',
	function ( $atts ): string {
		$atts = shortcode_atts(
			array(
				'simple_id'   => 0,
				'grouped_id'  => 0,
				'variable_id' => 0,
			),
			$atts
		);

		$simple_id   = absint( $atts['simple_id'] );
		$grouped_id  = absint( $atts['grouped_id'] );
		$variable_id = absint( $atts['variable_id'] );

		// The same acknowledgement string the core callers pass; the loaders
		// throw when it doesn't match.
		$consent_statement = 'I acknowledge that using experimental APIs means my theme or plugin will inevitably break in the next version of WooCommerce';

		$product        = wc_interactivity_api_load_product( $consent_statement, $simple_id );
		$child_products = wc_interactivity_api_load_purchasable_child_products( $consent_statement, $grouped_id );
		$variations     = wc_interactivity_api_load_variations( $consent_statement, $variable_id );

		ob_start();
		?>
		<div data-wp-interactive="woocommerce">
			<p data-testid="loader-simple-return-sku"><?php echo esc_html( $product['sku'] ?? '' ); ?></p>
			<p data-testid="loader-simple-state-sku" data-wp-text="state.products.<?php echo esc_attr( $simple_id ); ?>.sku"></p>

			<?php foreach ( $child_products as $child_id => $child_data ) : ?>
				<p data-testid="loader-grouped-child-return-sku-<?php echo esc_attr( $child_id ); ?>"><?php echo esc_html( $child_data['sku'] ?? '' ); ?></p>
				<p data-testid="loader-grouped-child-state-sku-<?php echo esc_attr( $child_id ); ?>" data-wp-text="state.products.<?php echo esc_attr( $child_id ); ?>.sku"></p>
			<?php endforeach; ?>

			<?php foreach ( $variations as $variation_id => $variation_data ) : ?>
				<p data-testid="loader-variation-return-sku-<?php echo esc_attr( $variation_id ); ?>"><?php echo esc_html( $variation_data['sku'] ?? '' ); ?></p>
				<p data-testid="loader-variation-state-sku-<?php echo esc_attr( $variation_id ); ?>" data-wp-text="state.productVariations.<?php echo esc_attr( $variation_id ); ?>.sku"></p>
			<?php endforeach; ?>
		</div>
		<?php
		return wp_interactivity_process_directives( (string) ob_get_clean() );
	}
);
