<?php
/**
 * Displays the attributes tab in the product data meta box.
 *
 * @package WooCommerce\Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wc_product_attributes;
// Array of defined attribute taxonomies.
$attribute_taxonomies = wc_get_attribute_taxonomies();
// Product attributes - taxonomies and custom, ordered, with visibility and variation attributes set.
$product_attributes = $product_object->get_attributes( 'edit' );
?>
<div id="product_attributes" class="panel wc-metaboxes-wrapper hidden">
	<div class="toolbar toolbar-top">
		<div id="message" class="inline notice woocommerce-message is-dismissible">
			<p class="help">
				<?php
				esc_html_e(
					'Add descriptive pieces of information that customers can use to search for this product on your store, such as “Material” or “Size”.',
					'woocommerce'
				);
				?>
				<button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'woocommerce' ); ?></span></button>
			</p>
		</div>
		<span class="expand-close">
			<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>
		</span>
		<div class="actions">
			<button type="button" class="button add_custom_attribute"><?php esc_html_e( 'Add new', 'woocommerce' ); ?></button>
			<select class="wc-attribute-search" data-placeholder="<?php esc_attr_e( 'Add existing', 'woocommerce' ); ?>" data-minimum-input-length="0">
			</select>
		</div>
	</div>
	<div class="product_attributes wc-metaboxes">
		<?php
		$i = -1;

		foreach ( $product_attributes as $attribute ) {
			++$i;
			$metabox_class = array();

			if ( $attribute->is_taxonomy() ) {
				$metabox_class[] = 'taxonomy';
				$metabox_class[] = $attribute->get_name();
			}

			include __DIR__ . '/html-product-attribute.php';
		}
		?>
	</div>
	<div class="toolbar toolbar-buttons">
		<span class="expand-close">
			<a href="#" class="expand_all"><?php esc_html_e( 'Expand', 'woocommerce' ); ?></a> / <a href="#" class="close_all"><?php esc_html_e( 'Close', 'woocommerce' ); ?></a>
		</span>
		<button type="button" aria-disabled="true" class="button save_attributes button-primary disabled"><?php esc_html_e( 'Save attributes', 'woocommerce' ); ?></button>
	</div>
	<?php do_action( 'woocommerce_product_options_attributes' ); ?>
</div>

<script type="text/template" id="tmpl-wc-modal-add-attribute-term">
	<div class="wc-backbone-modal wc-backbone-modal-add-attribute-term">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Create value', 'woocommerce' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
					<form class="wc-add-attribute-term-fields" action="" method="post">
						<label for="wc-modal-add-attribute-term-input"><?php esc_html_e( 'Name', 'woocommerce' ); ?></label>
						<input id="wc-modal-add-attribute-term-input" type="text" name="term" value="" />
					</form>
				</article>
				<footer>
					<div class="inner">
						<div>
							<button class="modal-close button button-large"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></button>
							<button id="btn-ok" disabled class="button button-primary button-large"><?php esc_html_e( 'OK', 'woocommerce' ); ?></button>
						</div>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
