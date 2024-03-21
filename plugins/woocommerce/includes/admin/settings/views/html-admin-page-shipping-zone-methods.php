<?php
/**
 * Shipping zone admin
 *
 * @package WooCommerce\Admin\Shipping
 */

use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;
use Automattic\WooCommerce\Blocks\Shipping\ShippingController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h2>
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping' ) ); ?>"><?php esc_html_e( 'Shipping zones', 'woocommerce' ); ?></a> &gt;
	<span class="wc-shipping-zone-name"><?php echo esc_html( $zone->get_zone_name() ? $zone->get_zone_name() : __( 'Zone', 'woocommerce' ) ); ?></span>
</h2>

<?php
// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
do_action( 'woocommerce_shipping_zone_before_methods_table', $zone );
?>

<table class="form-table wc-shipping-zone-settings">
	<tbody>
		<?php if ( 0 !== $zone->get_id() ) : ?>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="zone_name">
						<?php esc_html_e( 'Zone name', 'woocommerce' ); ?>
					</label>
					<p class="wc-shipping-zone-help-text">
						<?php esc_html_e( 'Give your zone a name! E.g. Local, or Worldwide.', 'woocommerce' ); ?>
					</p>
				</th>
				<td class="forminp">
					<input type="text" data-attribute="zone_name" name="zone_name" id="zone_name" value="<?php echo esc_attr( $zone->get_zone_name( 'edit' ) ); ?>" placeholder="<?php esc_attr_e( 'Zone name', 'woocommerce' ); ?>">
				</td>
			</tr>
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<label for="zone_locations">
						<?php esc_html_e( 'Zone regions', 'woocommerce' ); ?>
					</label>
					<p class="wc-shipping-zone-help-text">
						<?php esc_html_e( 'List the regions you\'d like to include in your shipping zone. Customers will be matched against these regions.', 'woocommerce' ); ?>
					</p>
				</th>
				<td>
					<div>
						<div id="wc-shipping-zone-region-picker-root"/>
					</div>
					<?php if ( empty( $postcodes ) ) : ?>
						<a class="wc-shipping-zone-postcodes-toggle" href="#"><?php esc_html_e( 'Limit to specific ZIP/postcodes', 'woocommerce' ); ?></a>
					<?php endif; ?>
					<div class="wc-shipping-zone-postcodes">
						<textarea name="zone_postcodes" data-attribute="zone_postcodes" id="zone_postcodes" placeholder="<?php esc_attr_e( 'List 1 postcode per line', 'woocommerce' ); ?>" class="input-text large-text" cols="25" rows="5"><?php echo esc_textarea( implode( "\n", $postcodes ) ); ?></textarea>
						<?php /* translators: WooCommerce link to setting up shipping zones */ ?>
						<span class="description"><?php printf( __( 'Postcodes containing wildcards (e.g. CB23*) or fully numeric ranges (e.g. <code>90210...99000</code>) are also supported. Please see the shipping zones <a href="%s" target="_blank">documentation</a> for more information.', 'woocommerce' ), 'https://woo.com/document/setting-up-shipping-zones/#section-3' ); ?></span><?php // @codingStandardsIgnoreLine. ?>
					</div>
				</td>
			</tr>
		<?php endif; ?>
		<tr valign="top" class="">
			<th scope="row" class="titledesc">
				<label>
					<?php esc_html_e( 'Shipping methods', 'woocommerce' ); ?>
				</label>
				<p class="wc-shipping-zone-help-text">
					<?php esc_html_e( 'Add the shipping methods you\'d like to make available to customers in this zone.', 'woocommerce' ); ?>
				</p>
			</th>
			<td class="">
				<table class="wc-shipping-zone-methods widefat">
					<thead>
						<tr>
							<th class="wc-shipping-zone-method-sort"></th>
							<th class="wc-shipping-zone-method-title"><?php esc_html_e( 'Title', 'woocommerce' ); ?></th>
							<th class="wc-shipping-zone-method-enabled"><?php esc_html_e( 'Enabled', 'woocommerce' ); ?></th>
							<th class="wc-shipping-zone-method-description"><?php esc_html_e( 'Description', 'woocommerce' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody class="wc-shipping-zone-method-rows wc-shipping-tables-tbody"></tbody>
				</table>
			</td>
		</tr>
		<tr>
			<th scope="row"></th>
			<td>
				<button type="submit" class="button button-primary wc-shipping-zone-add-method" value="<?php esc_attr_e( 'Add shipping method', 'woocommerce' ); ?>"><?php esc_html_e( 'Add shipping method', 'woocommerce' ); ?></button>
			</td>
		</tr>
	</tbody>
</table>

<?php
// phpcs:ignore WooCommerce.Commenting.CommentHooks.MissingHookComment
do_action( 'woocommerce_shipping_zone_after_methods_table', $zone );
?>

<p class="submit">
	<button type="submit" name="submit" id="submit" class="button button-primary button-large wc-shipping-zone-method-save" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>" disabled><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
</p>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row-blank">
	<tr>
		<td class="wc-shipping-zone-method-blank-state" colspan="5">
			<p><?php esc_html_e( 'You can add multiple shipping methods within this zone. Only customers within the zone will see them.', 'woocommerce' ); ?></p>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-shipping-zone-method-row">
	<tr data-id="{{ data.instance_id }}" data-enabled="{{ data.enabled }}">
		<td width="1%" class="wc-shipping-zone-method-sort"></td>
		<td class="wc-shipping-zone-method-title">
			{{{ data.title }}}
		</td>
		<td width="1%" class="wc-shipping-zone-method-enabled"><a href="#">{{{ data.enabled_icon }}}</a></td>
		<td class="wc-shipping-zone-method-description">
			{{{ data.method_description }}}
		</td>
		<td class="wc-shipping-zone-actions">
			<div>
				<a class="wc-shipping-zone-method-settings wc-shipping-zone-action-edit" href="admin.php?page=wc-settings&amp;tab=shipping&amp;instance_id={{ data.instance_id }}"><?php esc_html_e( 'Edit', 'woocommerce' ); ?></a> | <a href="#" class="wc-shipping-zone-method-delete wc-shipping-zone-actions"><?php esc_html_e( 'Delete', 'woocommerce' ); ?></a>
			</div>
		</td>
	</tr>
</script>

<script type="text/template" id="tmpl-wc-modal-shipping-method-settings">
	<div class="wc-backbone-modal wc-backbone-modal-shipping-method-settings">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1>
						<?php
						printf(
							/* translators: %s: shipping method title */
							esc_html__( 'Set up %s', 'woocommerce' ),
							'{{{ data.method.method_title.toLowerCase() }}}'
						);
						?>
					</h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article class="wc-modal-shipping-method-settings" data-id="{{{ data.instance_id }}}" data-status="{{{ data.status }}}"  data-shipping-classes-count="<?php echo count( WC()->shipping()->get_shipping_classes() ); ?>">
					<form action="" method="post">
						{{{ data.method.settings_html }}}
						<input type="hidden" name="instance_id" value="{{{ data.instance_id }}}" />
					</form>
					<a class="wc-shipping-method-add-class-costs" style="display:none;" target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=classes' ) ); ?>"><?php esc_html_e( 'Add shipping class costs', 'woocommerce' ); ?></a>
				</article>
				<footer>
					<div class="inner">
						<div>
							<button id="btn-back" class="button button-large wc-backbone-modal-back-{{ data.status === 'new' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Back', 'woocommerce' ); ?></button>
							<button id="btn-ok" data-status='{{ data.status }}' class="button button-primary button-large">
								<div class="wc-backbone-modal-action-{{ data.status === 'new' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Create and save', 'woocommerce' ); ?></div>
								<div class="wc-backbone-modal-action-{{ data.status === 'existing' ? 'active' : 'inactive' }}"><?php esc_html_e( 'Save', 'woocommerce' ); ?></div>
							</button>
						</div>
						<div class="wc-shipping-zone-method-modal-info wc-shipping-zone-method-modal-info-{{ data.status === 'existing' ? 'inactive' : 'active' }}"><?php esc_html_e( 'STEP 2 OF 2', 'woocommerce' ); ?></div>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>

<script type="text/template" id="tmpl-wc-modal-add-shipping-method">
	<div class="wc-backbone-modal wc-backbone-modal-add-shipping-method">
		<div class="wc-backbone-modal-content">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Create shipping method', 'woocommerce' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text"><?php esc_html_e( 'Close modal panel', 'woocommerce' ); ?></span>
					</button>
				</header>
				<article>
					<form action="" method="post">
						<fieldset class="wc-shipping-zone-method-selector">
							<legend class="screen-reader-text"><?php esc_html_e( 'Choose the shipping method you wish to add. Only shipping methods which support zones are listed.', 'woocommerce' ); ?></legend>
							<?php
							$methods = WC()->shipping()->load_shipping_methods();

							$methods_placed_in_order = array();
							$first_methods_ids       = array( 'free_shipping', 'flat_rate', 'local_pickup' );

							foreach ( $first_methods_ids as $first_method_id ) {
								foreach ( $methods as $key => $obj ) {
									if ( $obj->id === $first_method_id ) {
										$methods_placed_in_order[] = $obj;
										unset( $methods[ $key ] );
										break;
									}
								}
							}

							$methods_placed_in_order = array_merge( $methods_placed_in_order, array_values( $methods ) );

							foreach ( $methods_placed_in_order as $method ) {
								if ( CartCheckoutUtils::is_checkout_block_default() && ! ShippingController::is_legacy_local_pickup_active() && 'local_pickup' === $method->id ) {
									continue;
								}

								if ( ! $method->supports( 'shipping-zones' ) ) {
									continue;
								}

								echo '<div class="wc-shipping-zone-method-input"><input type="radio" value="' . esc_attr( $method->id ) . '" id="' . esc_attr( $method->id ) . '" name="add_method_id"/><label for="' . esc_attr( $method->id ) . '">' . esc_html( $method->get_method_title() ) . '<span class="dashicons dashicons-yes"></span></label></div>';
							}

							echo '<div class="wc-shipping-zone-method-input-help-text-container">';

							foreach ( $methods_placed_in_order as $method ) {
								if ( ! $method->supports( 'shipping-zones' ) ) {
									continue;
								}

								echo '<div id=' . esc_attr( $method->id ) . '-description class="wc-shipping-zone-method-input-help-text"><span>' . wp_kses_post( wpautop( $method->get_method_description() ) ) . '</span></div>';
							}

							if ( CartCheckoutUtils::is_checkout_block_default() ) {
								echo '<p class="wc-shipping-legacy-local-pickup-help-text-container">';

								if ( ShippingController::is_legacy_local_pickup_active() ) {
									printf(
										wp_kses(
										/* translators: %s: Local pickup settings page URL. */
											__( 'Explore a new enhanced delivery method that allows you to easily offer one or more pickup locations to your customers in the <a href="%s">Local pickup settings page</a>.', 'woocommerce' ),
											array( 'a' => array( 'href' => array() ) )
										),
										esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pickup_location' ) )
									);

								} else {
									printf(
										wp_kses(
										/* translators: %s: Local pickup settings page URL. */
											__( 'Local pickup: Set up pickup locations in the <a href="%s">Local pickup settings page</a>.', 'woocommerce' ),
											array( 'a' => array( 'href' => array() ) )
										),
										esc_url( admin_url( 'admin.php?page=wc-settings&tab=shipping&section=pickup_location' ) )
									);
								}

								echo '</p>';
							}

							echo '</div>';

							?>
						</fieldset>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-next" disabled class="button button-primary button-large disabled"><?php esc_html_e( 'Continue', 'woocommerce' ); ?></button>
						<div class="wc-shipping-zone-method-modal-info"><?php esc_html_e( 'STEP 1 OF 2', 'woocommerce' ); ?></div>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
