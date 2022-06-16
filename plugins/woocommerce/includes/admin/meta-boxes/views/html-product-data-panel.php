<?php
/**
 * Product data meta box.
 *
 * @package WooCommerce\Admin
 * @phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="panel-wrap product_data">

	<span class="type_box hidden"> &mdash;
		<label for="product-type">
			<select id="product-type" name="product-type">
				<optgroup label="<?php esc_attr_e( 'Product Type', 'woocommerce' ); ?>">
				<?php foreach ( wc_get_product_types() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php echo selected( $product_object->get_type(), $value, false ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
				</optgroup>
			</select>
		</label>

		<?php
		foreach ( self::get_product_type_options() as $key => $option ) :
			if ( metadata_exists( 'post', $post->ID, '_' . $key ) ) {
				$selected_value = is_callable( array( $product_object, "is_$key" ) ) ? $product_object->{"is_$key"}() : 'yes' === get_post_meta( $post->ID, '_' . $key, true );
			} else {
				$selected_value = 'yes' === ( isset( $option['default'] ) ? $option['default'] : 'no' );
			}
			?>
			<label for="<?php echo esc_attr( $option['id'] ); ?>" class="<?php echo esc_attr( $option['wrapper_class'] ); ?> tips" data-tip="<?php echo esc_attr( $option['description'] ); ?>">
				<?php echo esc_html( $option['label'] ); ?>:
				<input type="checkbox" name="<?php echo esc_attr( $option['id'] ); ?>" id="<?php echo esc_attr( $option['id'] ); ?>" <?php echo checked( $selected_value, true, false ); ?> />
			</label>
		<?php endforeach; ?>
	</span>
	
	<?php if ( true ) : ?>
		<dl class="product_data_cards wc-cards">
			<?php foreach ( self::get_product_data_tabs() as $key => $tab_data ) : ?>
				<dt class="clear <?php echo esc_attr( $key ); ?>_options_label <?php echo esc_attr( $key ); ?>_card_label <?php echo esc_attr( isset( $tab_data['class'] ) ? implode( ' ', (array) $tab_data['class'] ) : '' ); ?>">
					<span><?php echo esc_html( $tab_data['label'] ); ?></span>
				</dt>
				<dd class="card <?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_card <?php echo esc_attr( isset( $tab_data['class'] ) ? implode( ' ', (array) $tab_data['class'] ) : '' ); ?>">
					<?php self::output_tab( $tab_data ); ?>
				</dd>
			<?php endforeach; ?>
		</dl>
		<script type="text/javascript">
			for (let hiddenCard of document.querySelectorAll( '.product_data_cards .card > .hidden' ) ) {
				hiddenCard.classList.remove( 'hidden' );
			}
		</script>
	<?php else : ?>
		<ul class="product_data_tabs wc-tabs">
			<?php foreach ( self::get_product_data_tabs() as $key => $tab_data ) : ?>
				<li class="<?php echo esc_attr( $key ); ?>_options <?php echo esc_attr( $key ); ?>_tab <?php echo esc_attr( isset( $tab_data['class'] ) ? implode( ' ', (array) $tab_data['class'] ) : '' ); ?>">
					<a href="#<?php echo esc_attr( $tab_data['target'] ); ?>"><span><?php echo esc_html( $tab_data['label'] ); ?></span></a>
				</li>
			<?php endforeach; ?>
			<?php do_action( 'woocommerce_product_write_panel_tabs' ); ?>
		</ul>

		<?php
			self::output_tabs();
			self::output_variations();
			do_action( 'woocommerce_product_data_panels' );
			wc_do_deprecated_action( 'woocommerce_product_write_panels', array(), '2.6', 'Use woocommerce_product_data_panels action instead.' );
		?>
	<?php endif; ?>
	<div class="clear"></div>
</div>
