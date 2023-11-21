<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2 class="wc-shipping-zones-heading">
	<span><?php esc_html_e( 'Shipping zones', 'woocommerce' ); ?></span>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=new' ) ); ?>" class="button-primary"><?php esc_html_e( 'Add zone', 'woocommerce' ); ?></a>
</h2>
<p class="wc-shipping-zone-heading-help-text"><?php echo esc_html_e( 'A shipping zone consists of the region(s) you\'d like to ship to and the shipping method(s) offered. A shopper can only be matched to one zone, and we\'ll use their shipping address to show them the methods available in their area.', 'woocommerce' ); ?></p>
<table class="wc-shipping-zones widefat">
	<thead>
		<tr>
			<th class="wc-shipping-zone-sort"><?php echo wc_help_tip( __( 'Drag and drop to re-order your custom zones. This is the order in which they will be matched against the customer address.', 'woocommerce' ) ); ?></th>
			<th class="wc-shipping-zone-name"><?php esc_html_e( 'Zone name', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-region"><?php esc_html_e( 'Region(s)', 'woocommerce' ); ?></th>
			<th class="wc-shipping-zone-methods"><?php esc_html_e( 'Shipping method(s)', 'woocommerce' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody class="wc-shipping-zone-rows wc-shipping-tables-tbody"></tbody>

	<tfoot data-id="0" class="wc-shipping-zone-worldwide">
		<td width="1%" class="wc-shipping-zone-worldwide"></td>
		<td class="wc-shipping-zone-name">
			<?php esc_html_e( 'Rest of the world', 'woocommerce' ); ?>
		</td>
		<td class="wc-shipping-zone-region"><?php esc_html_e( 'An optional zone you can use to set the shipping method(s) available to any regions that have not been listed above.', 'woocommerce' ); ?></td>
		<td class="wc-shipping-zone-methods">
			<ul>
				<?php
				$worldwide = new WC_Shipping_Zone( 0 );
				$methods   = $worldwide->get_shipping_methods();
				uasort( $methods, 'wc_shipping_zone_method_order_uasort_comparison' );

				if ( ! empty( $methods ) ) {
					foreach ( $methods as $method ) {
						$class_name = 'yes' === $method->enabled ? 'method_enabled' : 'method_disabled';
						echo '<li class="wc-shipping-zone-method ' . esc_attr( $class_name ) . '">' . esc_html( $method->get_title() ) . '</li>';
					}
				} else {
					echo '<li>' . esc_html_e( 'No shipping methods offered to this zone.', 'woocommerce' ) . '</li>';
				}
				?>
			</ul>
		</td>
		<td class="wc-shipping-zone-actions">
			<a class="wc-shipping-zone-action-edit" href="admin.php?page=wc-settings&amp;tab=shipping&amp;zone_id=0"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a>
		</td>
	</tfoot>

</table>

<script type="text/html" id="tmpl-wc-shipping-zone-row-blank">
	<?php if ( 0 === $method_count ) : ?>
		<tr>
			<td class="wc-shipping-zones-blank-state" colspan="4">
				<p class="main"><?php _e( 'A shipping zone is a geographic region where a certain set of shipping methods and rates apply.', 'woocommerce' ); ?></p>
				<p><?php _e( 'For example:', 'woocommerce' ); ?></p>
				<ul>
					<li><?php _e( 'Local zone = California ZIP 90210 = Local pickup', 'woocommerce' ); ?>
					<li><?php _e( 'US domestic zone = All US states = Flat rate shipping', 'woocommerce' ); ?>
					<li><?php _e( 'Europe zone = Any country in Europe = Flat rate shipping', 'woocommerce' ); ?>
				</ul>
				<p><?php _e( 'Add as many zones as you need &ndash; customers will only see the methods available for their address.', 'woocommerce' ); ?></p>
				<a class="button button-primary wc-shipping-zone-add" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&zone_id=new' ) ); ?>"><?php _e( 'Add shipping zone', 'woocommerce' ); ?></a>
			</td>
		</tr>
	<?php endif; ?>
</script>

<script type="text/html" id="tmpl-wc-shipping-zone-row">
	<tr data-id="{{ data.zone_id }}">
		<td width="1%" class="wc-shipping-zone-sort"></td>
		<td class="wc-shipping-zone-name">
			{{ data.zone_name }}
		</td>
		<td class="wc-shipping-zone-region">
			{{ data.formatted_zone_location }}
		</td>
		<td class="wc-shipping-zone-methods">
			<div><ul></ul></div>
		</td>
		<td class="wc-shipping-zone-actions">
			<div>
				<a class="wc-shipping-zone-action-edit" href="admin.php?page=wc-settings&amp;tab=shipping&amp;zone_id={{ data.zone_id }}"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-zone-delete wc-shipping-zone-actions"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a>
			</div>
		</td>
	</tr>
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
								foreach ( WC()->shipping()->load_shipping_methods() as $method ) {
									if ( ! $method->supports( 'shipping-zones' ) ) {
										continue;
									}
									echo '<option data-description="' . esc_attr( wp_kses_post( wpautop( $method->get_method_description() ) ) ) . '" value="' . esc_attr( $method->id ) . '">' . esc_html( $method->get_method_title() ) . '</li>';
								}
								?>
							</select>
							<input type="hidden" name="zone_id" value="{{{ data.zone_id }}}" />
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
