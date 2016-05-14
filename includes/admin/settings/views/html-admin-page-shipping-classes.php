<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2>
	<?php _e( 'Shipping Classes', 'woocommerce' ); ?>
	<?php echo wc_help_tip( __( 'Shipping classes can be used to group products of similar type and can be used by some Shipping Methods (such as Flat Rate Shipping) to provide different rates to different classes of product.', 'woocommerce' ) ); ?>
</h2>

<table class="wc-shipping-classes widefat">
	<thead>
		<tr>
			<th class="wc-shipping-class-name"><?php esc_html_e( 'Shipping Class', 'woocommerce' ); ?></th>
			<th class="wc-shipping-class-slug"><?php esc_html_e( 'Slug', 'woocommerce' ); ?></th>
			<th class="wc-shipping-class-description"><?php esc_html_e( 'Description', 'woocommerce' ); ?></th>
			<th class="wc-shipping-class-count"><?php esc_html_e( 'Product Count', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="4">
				<input type="submit" name="save" class="button button-primary wc-shipping-class-save" value="<?php esc_attr_e( 'Save Shipping Classes', 'woocommerce' ); ?>" disabled />
				<a class="button button-secondary wc-shipping-class-add" href="#"><?php esc_html_e( 'Add Shipping Class', 'woocommerce' ); ?></a>
			</td>
		</tr>
	</tfoot>
	<tbody class="wc-shipping-class-rows"></tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-class-row-blank">
	<tr>
		<td class="wc-shipping-classes-blank-state" colspan="4"><p><?php _e( 'No Shipping classes have been created.', 'woocommerce' ); ?></p></td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-class-row">
	<tr data-id="{{ data.term_id }}">
		<td class="wc-shipping-class-name">
			<div class="view">
				{{ data.name }}
				<div class="row-actions">
					<a class="wc-shipping-class-edit" href="#"><?php _e( 'Edit', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-class-delete"><?php _e( 'Remove', 'woocommerce' ); ?></a>
				</div>
			</div>
			<div class="edit">
				<input type="text" name="name[{{ data.term_id }}]" data-attribute="name" value="{{ data.name }}" placeholder="<?php esc_attr_e( 'Shipping Class Name', 'woocommerce' ); ?>" />
				<div class="row-actions">
					<a class="wc-shipping-class-cancel-edit" href="#"><?php _e( 'Cancel changes', 'woocommerce' ); ?></a>
				</div>
			</div>
		</td>
		<td class="wc-shipping-class-slug">
			<div class="view">{{ data.slug }}</div>
			<div class="edit"><input type="text" name="slug[{{ data.term_id }}]" data-attribute="slug" value="{{ data.slug }}" placeholder="<?php esc_attr_e( 'Slug', 'woocommerce' ); ?>" /></div>
		</td>
		<td class="wc-shipping-class-description">
			<div class="view">{{ data.description }}</div>
			<div class="edit"><input type="text" name="description[{{ data.term_id }}]" data-attribute="description" value="{{ data.description }}" placeholder="<?php esc_attr_e( 'Description for your reference', 'woocommerce' ); ?>" /></div>
		</td>
		<td class="wc-shipping-class-count">
			<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product&product_shipping_class=' ) ); ?>{{data.slug}}">{{ data.count }}</a>
		</td>
	</tr>
</script>
