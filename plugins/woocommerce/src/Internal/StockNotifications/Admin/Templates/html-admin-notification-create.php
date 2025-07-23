<?php
/**
 * Admin View: Notification create
 *
 * @since 10.2.0
 */

declare( strict_types = 1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Internal\StockNotifications\Admin\NotificationsPage;
?>
<div class="wrap woocommerce-customer-stock-notifications">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Add Notification', 'woocommerce' ); ?></h1>
	<a href="<?php echo esc_url( NotificationsPage::PAGE_URL ); ?>" class="page-title-action"><?php esc_html_e( 'View All', 'woocommerce' ); ?></a>

	<hr class="wp-header-end">

	<form method="POST" id="edit-notification-form">
	<?php wp_nonce_field( 'woocommerce-customer-stock-notification-create', 'customer_stock_notification_create_security' ); ?>

	<div id="poststuff">
		<div id="post-body" class="columns-2">

			<!-- SIDEBAR -->
			<div id="postbox-container-1" class="postbox-container">

				<div id="woocommerce-order-actions" class="postbox">

					<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Notification actions', 'woocommerce' ); ?></span></h2>

					<div class="inside">
						<ul class="order_actions submitbox">

							<li class="wide" id="actions">
								<select name="wc_customer_stock_notification_action" disabled="disabled">
									<option value=""><?php esc_html_e( 'Choose an action...', 'woocommerce' ); ?></option>
								</select>
								<button class="button wc-reload" disabled="disabled"><span><?php esc_html_e( 'Apply', 'woocommerce' ); ?></span></button>
							</li>

							<li class="wide">
								<button type="submit" class="button save_order button-primary" name="save" value="<?php esc_attr_e( 'Create', 'woocommerce' ); ?>"><?php esc_html_e( 'Create', 'woocommerce' ); ?></button>
							</li>

						</ul>
					</div>

				</div><!-- .postbox -->

			</div><!-- #container1 -->

			<!-- MAIN -->
			<div id="postbox-container-2" class="postbox-container">

				<div id="notification-data" class="postbox notification-data notification-data--create">
					<div class="notification-data__row notification-data__row--columns">

						<div class="notification-data__header-column">

							<h2 class="notification-data__header">
								<?php esc_html_e( 'Notification details', 'woocommerce' ); ?>
							</h2>

						</div>

					</div><!-- #row -->

					<div class="notification-data__row notification-data__row--columns">

						<div class="notification-data__form-field">
							<label><?php esc_html_e( 'Customer', 'woocommerce' ); ?></label>
							<?php
							$user_string = '';
							$user_id     = 0;

							// phpcs:disable WordPress.Security.NonceVerification.Recommended
							if ( ! empty( $_REQUEST['user_id'] ) ) {

								$user_id = absint( wp_unslash( $_REQUEST['user_id'] ) );
								if ( $user_id > 0 ) {
									$user = get_user_by( 'id', absint( $user_id ) );
									if ( $user ) {
										$user_string = sprintf(
											/* translators: 1: user display name 2: user ID 3: user email */
											esc_html__( '%1$s (#%2$s &ndash; %3$s)', 'woocommerce' ),
											$user->display_name,
											absint( $user->ID ),
											$user->user_email
										);
									}
								}
							}

							$email = isset( $_REQUEST['user_email'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['user_email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
							?>
							<select class="wc-customer-search" name="user_id" data-placeholder="<?php esc_attr_e( 'Search for a customer&hellip;', 'woocommerce' ); ?>" data-allow_clear="true">
								<?php if ( $user_string && $user_id ) { ?>
									<option value="<?php echo esc_attr( $user_id ); ?>" selected="selected"><?php echo wp_kses_post( htmlspecialchars( $user_string, ENT_COMPAT ) ); ?><option>
								<?php } ?>
							</select>
							<div class="divider"></div>
							<span class="or_relation_label"><?php esc_html_e( '&mdash;&nbsp;or&nbsp;&mdash;', 'woocommerce' ); ?></span>
							<input type="email" class="or_relation_label__input" placeholder="<?php esc_html_e( 'Enter customer e-mail&hellip;', 'woocommerce' ); ?>" name="user_email" value="<?php echo esc_attr( $email ); ?>"/>

							<div class="wp-clearfix"></div>
						</div>

						<div class="notification-data__form-field">

							<label><?php esc_html_e( 'Product', 'woocommerce' ); ?></label>
							<?php
							$product_string = '';
							$product_id     = 0;

							// phpcs:disable WordPress.Security.NonceVerification.Recommended
							if ( ! empty( $_REQUEST['product_id'] ) ) {

								$product_id = absint( wp_unslash( $_REQUEST['product_id'] ) );
								if ( $product_id > 0 ) {
									$product = wc_get_product( $product_id );
									if ( is_a( $product, 'WC_Product' ) ) {
										$product_string = sprintf(
											/* translators: 1: product title 2: product ID */
											esc_html__( '%1$s (#%2$s)', 'woocommerce' ),
											$product->get_parent_id() ? $product->get_name() : $product->get_title(),
											absint( $product->get_id() )
										);
									}
								}
							}
							// phpcs:enable WordPress.Security.NonceVerification.Recommended
							$excluded_product_types = array_diff( array_keys( wc_get_product_types() ), array( 'simple', 'variable' ) );
							?>
							<select class="wc-product-search" name="product_id" data-action="woocommerce_json_search_products_and_variations" data-exclude_type="<?php echo esc_attr( implode( ',', $excluded_product_types ) ); ?>" data-display_stock="true"data-placeholder="<?php esc_attr_e( 'Select product&hellip;', 'woocommerce' ); ?>" data-allow_clear="true">
								<?php if ( $product_string && $product_id ) { ?>
									<option value="<?php echo esc_attr( $product_id ); ?>" selected="selected"><?php echo wp_kses_post( htmlspecialchars( $product_string, ENT_COMPAT ) ); ?><option>
								<?php } ?>
							</select>
						</div>

					</div><!-- #row -->

				</div><!-- .postbox -->

			</div><!-- #container2 -->

		</div><!-- #post-body -->
	</div>

	</form>

</div>