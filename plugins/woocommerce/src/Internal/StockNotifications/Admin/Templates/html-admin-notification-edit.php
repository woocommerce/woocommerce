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
use Automattic\WooCommerce\Internal\StockNotifications\Enums\NotificationStatus;
?>
<div class="wrap woocommerce woocommerce-customer-stock-notifications">

	<h1 class="wp-heading-inline"><?php esc_html_e( 'Edit Notification', 'woocommerce' ); ?></h1>
	<a href="<?php echo esc_url( NotificationsPage::PAGE_URL ); ?>" class="page-title-action"><?php esc_html_e( 'View All', 'woocommerce' ); ?></a>
	<a href="<?php echo esc_url( add_query_arg( array( 'notification_action' => 'create' ), NotificationsPage::PAGE_URL ) ); ?>" class="page-title-action"><?php esc_html_e( 'Add New', 'woocommerce' ); ?></a>

	<hr class="wp-header-end">

	<form method="POST" id="edit-notification-form">
	<?php wp_nonce_field( 'woocommerce-customer-stock-notification-edit', 'customer_stock_notification_edit_security' ); ?>

	<div id="poststuff">
		<div id="post-body" class="columns-2">

			<!-- SIDEBAR -->
			<div id="postbox-container-1" class="postbox-container">

				<div id="woocommerce-order-actions" class="postbox">

					<h2 class="hndle ui-sortable-handle"><span><?php esc_html_e( 'Notification actions', 'woocommerce' ); ?></span></h2>

					<div class="inside">
						<ul class="order_actions submitbox">

							<li class="wide" id="actions">
								<select name="wc_customer_stock_notification_action">
									<option value=""><?php esc_html_e( 'Choose an action...', 'woocommerce' ); ?></option>
									<?php if ( $notification->get_status() === NotificationStatus::ACTIVE ) : ?>
										<option value="send_notification"><?php esc_html_e( 'Send', 'woocommerce' ); ?></option>
										<option value="cancel_notification"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></option>
									<?php elseif ( $notification->get_status() === NotificationStatus::PENDING ) : ?>
										<option value="send_verification_email"><?php esc_html_e( 'Resend verification email', 'woocommerce' ); ?></option>
										<option value="activate_notification"><?php esc_html_e( 'Activate', 'woocommerce' ); ?></option>
									<?php elseif ( $notification->get_status() === NotificationStatus::CANCELLED ) : ?>
										<option value="activate_notification"><?php esc_html_e( 'Activate', 'woocommerce' ); ?></option>
									<?php elseif ( $notification->get_status() === NotificationStatus::SENT ) : ?>
										<option value="activate_notification"><?php esc_html_e( 'Activate', 'woocommerce' ); ?></option>
										<option value="cancel_notification"><?php esc_html_e( 'Cancel', 'woocommerce' ); ?></option>
									<?php endif; ?>
								</select>
								<button class="button wc-reload"><span><?php esc_html_e( 'Apply', 'woocommerce' ); ?></span></button>
							</li>

							<li class="wide">
								<div id="delete-action">
									<a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( sprintf( NotificationsPage::PAGE_URL . '&notification_action=delete&notification_id=%d', $notification->get_id() ) ), 'delete_customer_stock_notification' ) ); ?>"><?php esc_html_e( 'Delete permanently', 'woocommerce' ); ?></a>
								</div>

								<button type="submit" class="button save_order button-primary" name="save" value="<?php esc_attr_e( 'Update', 'woocommerce' ); ?>"><?php esc_html_e( 'Update', 'woocommerce' ); ?></button>
							</li>

						</ul>
					</div>

				</div><!-- .postbox -->

			</div><!-- #container1 -->

			<!-- MAIN -->
			<div id="postbox-container-2" class="postbox-container">

				<div id="notification-data" class="postbox notification-data">

					<div class="notification-data__row notification-data__row--columns">

						<div class="notification-data__header-column">

							<h2 class="notification-data__header">
								<?php
								/* translators: %s: Notification ID */
								echo esc_html( sprintf( __( 'Notification #%d details', 'woocommerce' ), $notification->get_id() ) );
								?>
							</h2>

						</div>

						<div class="notification-data__status-column">
							<?php
							if ( $notification->get_status() === NotificationStatus::PENDING ) {
								$notification_status = 'cancelled';
								$label               = _x( 'Pending', 'stock notification status', 'woocommerce' );
							} elseif ( $notification->get_status() === NotificationStatus::CANCELLED ) {
								$notification_status = 'cancelled';
								$label               = _x( 'Cancelled', 'stock notification status', 'woocommerce' );
							} elseif ( $notification->get_status() === NotificationStatus::SENT ) {
								$notification_status = 'cancelled';
								$label               = _x( 'Sent', 'stock notification status', 'woocommerce' );
							} else {
								$notification_status = 'completed';
								$label               = _x( 'Active', 'stock notification status', 'woocommerce' );
							}

							printf( '<mark class="order-status %s"><span>%s</span></mark>', esc_attr( sanitize_html_class( 'status-' . $notification_status ) ), esc_html( $label ) );

							?>
						</div>

					</div><!-- #row -->

					<div class="notification-data__row notification-data__row--columns">

						<div class="notification-data__form-field">
							<label><?php esc_html_e( 'Customer', 'woocommerce' ); ?></label>
							<?php
							$user_string = '&mdash;';
							$user_id     = $notification->get_user_id();
							$user        = $user_id ? get_user_by( 'id', $user_id ) : null;
							if ( is_a( $user, 'WP_User' ) ) {
								$user_string = $user->display_name;
							} elseif ( filter_var( $notification->get_user_email(), FILTER_VALIDATE_EMAIL ) ) {
								$user_string = $notification->get_user_email();
							}
							?>
							<p class="notification-data__customer-data"><?php echo esc_html( $user_string ); ?></p>

							<div class="form-field__actions">
								<?php if ( isset( $user ) && is_a( $user, 'WP_User' ) ) { ?>
									<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php esc_html_e( 'View profile &rarr;', 'woocommerce' ); ?></a>
								<?php } ?>
								<a href="<?php echo esc_url( admin_url( NotificationsPage::PAGE_URL . '&s=' . rawurlencode( $notification->get_user_email() ) ) ); ?>"><?php esc_html_e( 'View notifications &rarr;', 'woocommerce' ); ?></a>
							</div>
						</div>

						<div class="notification-data__form-field">

							<label><?php esc_html_e( 'Product', 'woocommerce' ); ?></label>

							<div class="notification-data__product-data">
								<?php
								$product = $notification->get_product();
								if ( is_a( $product, 'WC_Product' ) ) {
									include __DIR__ . '/html-product-data-admin.php';
								} else {
									?>
									<small><?php esc_html_e( 'Product not found.', 'woocommerce' ); ?></small>
									<?php
								}
								?>
							</div>

						</div>

					</div><!-- #row -->

					<div class="notification-data__meta">
						<div class="notification-data__row notification-data__row--columns">

							<div class="notification-data__meta-column">
								<div class="notification-data__meta-data">
									<label><?php esc_html_e( 'Waiting', 'woocommerce' ); ?></label>
									<span>
										<?php
										if ( ! $notification->get_date_created() || $notification->get_status() !== 'active' ) {
											$t_time    = __( '&mdash;', 'woocommerce' );
											$h_time    = $t_time;
											$time_diff = 0;
										} else {
											$date_created_timestamp = $notification->get_date_created()->getTimestamp();
											$t_time                 = date_i18n( _x( 'Y/m/d g:i:s a', 'list table date hover format', 'woocommerce' ), $date_created_timestamp );
											$time_diff              = time() - $date_created_timestamp;

											if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
												/* translators: %s: human time diff */
												$h_time = wp_kses_post( human_time_diff( $date_created_timestamp ) );
											} else {
												$h_time = date_i18n( wc_date_format(), $date_created_timestamp );
											}
										}
										?>
										<span title="<?php echo esc_attr( $t_time ); ?>"><?php echo esc_html( $h_time ); ?></span>
									</span>
								</div>
								<div class="notification-data__meta-data">
									<label><?php esc_html_e( 'Signed up', 'woocommerce' ); ?></label>
									<?php
									$date_created = $notification->get_date_created();

									if ( ! $date_created ) {
										$t_time = __( '&mdash;', 'woocommerce' );
										$h_time = $t_time;
									} else {
										$date_created = $date_created->getTimestamp();
										$t_time       = date_i18n( _x( 'Y/m/d g:i:s a', 'list table date hover format', 'woocommerce' ), $date_created );
										$h_time       = date_i18n( wc_date_format(), $date_created );
									}
									?>
									<span title="<?php echo esc_attr( $t_time ); ?>"><?php echo esc_html( $h_time ); ?></span>
								</div>
							</div><!-- .column -->

							<div class="notification-data__meta-column">
								<div class="notification-data__meta-data">
									<label><?php esc_html_e( 'Signed-up customers', 'woocommerce' ); ?></label>
									<span>
										<?php
										echo absint( $signed_up_customers );

										if ( $signed_up_customers > 0 ) {
											?>
											<a href="<?php echo esc_attr( add_query_arg( array( 'customer_stock_notifications_product_filter' => $notification->get_product_id() ), NotificationsPage::PAGE_URL ) ); ?>"><?php esc_html_e( 'View notifications &rarr;', 'woocommerce' ); ?></a>
										<?php } ?>
									</span>
								</div>
								<?php
								$attributes = $notification->get_product_formatted_variation_list( true );
								if ( ! empty( $attributes ) ) {
									?>
									<div class="notification-data__meta-data">
										<label><?php esc_html_e( 'Attributes', 'woocommerce' ); ?></label>
										<span>
											<?php echo wp_kses_post( $attributes ); ?>
										</span>
									</div>
								<?php } ?>

							</div><!-- .column -->

						</div>
					</div>

				</div><!-- .postbox -->

			</div><!-- #container2 -->

		</div><!-- #post-body -->
	</div>

	</form>

</div>
