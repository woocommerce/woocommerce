<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2>
	<a href="<?php echo admin_url( 'admin.php?page=wc-settings&tab=shipping' ); ?>"><?php _e( 'Shipping Zones', 'woocommerce' ); ?></a> &gt;
	<?php echo esc_html( $zone->get_zone_name() ); ?>
	<?php echo wc_help_tip( __( 'The following shipping methods apply to customers with shipping addresses within this zone.', 'woocommerce' ) ); ?>
</h2>

<?php do_action( 'woocommerce_shipping_zone_before_methods_table' ); ?>

<table class="wc-shipping-zone-methods widefat">
	<thead>
		<tr>
			<th class="wc-shipping-zone-method-sort"><?php echo wc_help_tip( __( 'Drag and drop to re-order your shipping methods. This is the order in which they will display during checkout.', 'woocommerce' ) ); ?></th>
			<th class="wc-shipping-zone-method-title"><?php esc_html_e( 'Title', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-method-enabled"><?php esc_html_e( 'Enabled', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-method-type"><?php esc_html_e( 'Type', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-method-description"><?php esc_html_e( 'Description', 'woocommerce' ); ?></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="5">
				<input type="submit" name="save" class="button button-primary wc-shipping-zone-method-save" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>" disabled />
				<input type="submit" class="button button-secondary wc-shipping-zone-add-method" value="<?php esc_attr_e( 'Add shipping method', 'woocommerce' ); ?>" />
			</td>
		</tr>
	</tfoot>
	<tbody class="wc-shipping-zone-method-rows"></tbody>
</table>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row-blank">
	<tr>
		<td class="wc-shipping-zone-method-blank-state" colspan="5">
			<p class="main"><?php _e( 'Add shipping methods to this zone', 'woocommerce' ); ?></p>
			<p><?php _e( 'You can add multiple shipping methods within this zone. Only customers within the zone will see them.', 'woocommerce' ); ?></p>
			<p><?php _e( 'Click "Add shipping method" to get started.', 'woocommerce' ); ?></p>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row">
	<tr data-id="{{ data.instance_id }}" data-enabled="{{ data.enabled }}">
		<td width="1%" class="wc-shipping-zone-method-sort"></td>
		<td class="wc-shipping-zone-method-title">
			<a class="wc-shipping-zone-method-settings" href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}">{{ data.title }}</a>
			<div class="row-actions">
				<a class="wc-shipping-zone-method-settings" href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}"><?php _e( 'Settings', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-zone-method-delete"><?php _e( 'Remove', 'woocommerce' ); ?></a>
			</div>
		</td>
		<td width="1%" class="wc-shipping-zone-method-enabled"><a href="#">{{{ data.enabled_icon }}}</a></td>
		<td class="wc-shipping-zone-method-type">{{ data.method_title }}</td>
		<td class="wc-shipping-zone-method-description">{{ data.method_description }}</td>
	</tr>
</script>

<script type="text/template" id="tmpl-wc-modal-shipping-method-settings">
	<div class="wc-backbone-modal wc-backbone-modal-shipping-method-settings">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php echo esc_html( sprintf( _x( '%s Settings', 'Shipping Method Settings', 'woocommerce' ), '{{{ data.method.method_title }}}' ) ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article class="wc-modal-shipping-method-settings">
					<form action="" method="post">
						{{{ data.method.settings_html }}}
						<input type="hidden" name="instance_id" value="{{{ data.instance_id }}}" />
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Save changes', 'woocommerce' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-wc-modal-add-shipping-method">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php _e( 'Add shipping method', 'woocommerce' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php _e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
					<form action="" method="post">
						<div class="wc-shipping-zone-method-selector">
							<p><?php esc_html_e( 'Choose the shipping method you wish to add. Only shipping methods which support zones are listed.', 'woocommerce' ); ?></p>

							<select name="add_method_id">
								<?php
									foreach ( WC()->shipping->load_shipping_methods() as $method ) {
										if ( ! $method->supports( 'shipping-zones' ) ) {
											continue;
										}

										echo '<option data-description="' . esc_attr( $method->method_description ) . '" value="' . esc_attr( $method->id ) . '">' . esc_attr( $method->method_title ) . '</li>';
									}
								?>
							</select>
						</div>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php _e( 'Add shipping method', 'woocommerce' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
