<?php defined( 'ABSPATH' ) or exit(); ?>

<div class="wrap woocommerce wc_addons_wrap wc-helper">
	<?php include( WC_Helper::get_view_filename( 'html-section-nav.php' ) ); ?>
	<h1 class="screen-reader-text"><?php _e( 'WooCommerce Extensions', 'woocommerce' ); ?></h1>

	<?php include( WC_Helper::get_view_filename( 'html-section-notices.php' ) ); ?>
	<?php include( WC_Helper::get_view_filename( 'html-section-account.php' ) ); ?>

	<h2><?php _e( 'Subscriptions', 'woocommerce' ); ?></h2>
	<p><?php _e( 'Below is a list of products available on your WooCommerce.com account. To receive plugin updates please make sure the product is installed, activated and connected to your WooCommerce.com account.', 'woocommerce' ); ?></p>

	<table class="wp-list-table widefat fixed striped">
		<?php if ( ! empty( $subscriptions ) ) : ?>
			<?php foreach ( $subscriptions as $subscription ) : ?>
				<?php
					$installed = ! empty( $subscription['local']['installed'] );
					$connected = $subscription['active'];
					$product_id = $subscription['product_id'];
					$update_available = false;

					if ( $installed && ! empty( $updates[ $product_id ] ) &&
						version_compare( $updates[ $product_id ]['version'], $subscription['local']['version'], '>' ) ) {
						$update_available = true;
					}

					$download_url = $subscription['product_url'];
					if ( ! $installed && ! empty( $updates[ $product_id ]['package'] ) ) {
						$download_url = $updates[ $product_id ]['package'];
					}

					$classes = array(
						'color-bar' => true,
						'expired' => $subscription['expired'],
						'expiring' => $subscription['expiring'],
						'update-available' => $update_available,
						'autorenews' => $subscription['autorenew'],
					);

					$classes = array_filter( $classes, function( $i ) {
						return (bool) $i;
					} );

					$classes = array_keys( $classes );
				?>
				<tbody>
				<tr class="wp-list-table__row is-ext-header">
					<td class="wp-list-table__ext-details <?php echo implode( ' ', $classes ); ?>">
						<div class="wp-list-table__ext-title">
							<a href="<?php echo esc_url( $subscription['product_url'] ); ?>" target="_blank"><?php
								echo esc_html( $subscription['product_name'] ); ?></a>
						</div>

						<div class="wp-list-table__ext-description">
							<?php if ( $subscription['expired'] ) : ?>
								<span class="renews">
									<strong><?php _e( 'Expired :(', 'woocommerce' ); ?></strong>
									<?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?>
								</span>
							<?php elseif ( $subscription['autorenew'] ) : ?>
								<span class="renews">
									<?php _e( 'Auto renews on:', 'woocommerce' ); ?>
									<?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?>
								</span>
							<?php elseif ( $subscription['expiring'] ) : ?>
								<span class="renews">
									<strong><?php _e( 'Expiring soon!', 'woocommerce' ); ?></strong>
									<?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?>
								</span>
							<?php else : ?>
								<span class="renews">
									<?php _e( 'Expires on:', 'woocommerce' ); ?>
									<?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?>
								</span>
							<?php endif; ?>

							<br/>
							<span class="subscription">
							<?php
								if ( $subscription['sites_max'] > 0 ) {
									/* translators: %1$d: sites active, %2$d max sites active */
									printf( __( 'Subscription: Using %1$d of %2$d sites available', 'woocommerce' ), $subscription['sites_active'], $subscription['sites_max'] );
								} else {
									_e( 'Subscription: Unlimited', 'woocommerce' );
								}
							?>
							</span>
						</div>
					</td>
					<td class="wp-list-table__ext-actions">
						<?php if ( ! $installed && ! $subscription['expired'] ) : ?>
							<a class="button" href="<?php echo esc_url( $download_url ); ?>" target="_blank"><?php _e( 'Download', 'woocommerce' ); ?></a>
						<?php elseif ( $connected ) : ?>
							<span class="form-toggle__wrapper">
								<a href="<?php echo esc_url( $subscription['deactivate_url'] ); ?>" class="form-toggle active is-compact" role="link" aria-checked="true"><?php _e( 'Active', 'woocommerce' ); ?></a>
								<label class="form-toggle__label" for="activate-extension">
									<span class="form-toggle__label-content">
										<label for="activate-extension"><?php _e( 'Active', 'woocommerce' ); ?></label>
									</span>
									<span class="form-toggle__switch"></span>
								</label>
							</span>
						<?php elseif ( ! $subscription['expired'] ) : ?>
							<span class="form-toggle__wrapper">
								<a href="<?php echo esc_url( $subscription['activate_url'] ); ?>" class="form-toggle is-compact" role="link" aria-checked="false"><?php _e( 'Inactive', 'woocommerce' ); ?></a>
								<label class="form-toggle__label" for="activate-extension">
									<span class="form-toggle__label-content">
										<label for="activate-extension"><?php _e( 'Inactive', 'woocommerce' ); ?></label>
									</span>
									<span class="form-toggle__switch"></span>
								</label>
							</span>
						<?php else : ?>
							<span class="form-toggle__wrapper">
								<span class="form-toggle disabled is-compact"><?php _e( 'Inactive', 'woocommerce' ); ?></span>
								<label class="form-toggle__label" for="activate-extension">
									<span class="form-toggle__label-content">
										<label for="activate-extension"><?php _e( 'Inactive', 'woocommerce' ); ?></label>
									</span>
									<span class="form-toggle__switch"></span>
								</label>
							</span>
						<?php endif; ?>
					</td>
				</tr>

				<?php if ( $update_available && ! $subscription['expired'] ) : ?>
				<tr class="wp-list-table__row wp-list-table__ext-updates">
					<td class="wp-list-table__ext-status update-available">
						<p><span class="dashicons dashicons-update"></span>
							<?php /* translators: %s: version number */ ?>
							<?php printf( __( 'Version %s is <strong>available</strong>.', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?>
							<?php if ( ! $connected ) : ?>
								<?php _e( 'To enable this update you need to <strong>activate</strong> this subscription.', 'woocommerce' ); ?>
							<?php endif; ?>
						</p>
					</td>
					<td class="wp-list-table__ext-actions">
						<?php if ( $connected ) : ?>
							<a class="button" href="<?php echo esc_url( $subscription['update_url'] ); ?>"><?php _e( 'Update', 'woocommerce' ); ?></a>
							<!-- TODO: Activate & Update -->
						<?php endif; ?>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ( $update_available && $subscription['expired'] ) : ?>
				<tr class="wp-list-table__row wp-list-table__ext-updates">
					<td class="wp-list-table__ext-status expired">
						<p><span class="dashicons dashicons-info"></span>
							<?php /* translators: %s: version number */ ?>
							<?php printf( __( 'Version %s is <strong>available</strong>.', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?>
							<?php _e( 'To enable this update you need to <strong>purchase</strong> a new subscription.', 'woocommerce' ); ?>
						</p>
					</td>
					<td class="wp-list-table__ext-actions">
						<a class="button" href="<?php echo esc_url( $subscription['product_url'] ); ?>" target="_blank"><?php _e( 'Purchase', 'woocommerce' ); ?></a>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ( $subscription['expiring'] && ! $subscription['autorenew'] ) : ?>
				<tr class="wp-list-table__row wp-list-table__ext-updates">
					<td class="wp-list-table__ext-status expired">
						<p><span class="dashicons dashicons-info"></span>
							<?php _e( 'Subscription is <strong>expiring</strong> soon.', 'woocommerce' ); ?>
						</p>
					</td>
					<td class="wp-list-table__ext-actions">
						<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/" target="_blank"><?php _e( 'Enable auto-renew', 'woocommerce' ); ?></a>
					</td>
				</tr>
				<?php endif; ?>

				<?php if ( ! $connected && $subscription['sites_max'] > 0 && $subscription['sites_active'] >= $subscription['sites_max'] ) : ?>
				<tr class="wp-list-table__row wp-list-table__ext-updates">
					<td class="wp-list-table__ext-status expired">
						<p><span class="dashicons dashicons-info"></span>
							<?php _e( 'You are already using the <strong>maximum number of sites available</strong> with your current subscription.', 'woocommerce' ); ?>
						</p>
					</td>
					<td class="wp-list-table__ext-actions">
						<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/" target="_blank"><?php _e( 'Upgrade', 'woocommerce' ); ?></a>
					</td>
				</tr>
				<?php endif; ?>

				</tbody>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="3"><em><?php _e( 'Could not find any subscriptions on your WooCommerce.com account', 'woocommerce' ); ?></td>
			</tr>
		<?php endif; ?>
		</tbody>
	</table>

	<?php if ( ! empty( $no_subscriptions ) ) : ?>
		<h2><?php _e( 'Installed Extensions without a Subscription', 'woocommerce' ); ?></h2>
		<p>Below is a list of WooCommerce.com products available on your site - but are either out-dated or do not have a valid subscription.</p>

		<table class="wp-list-table widefat fixed striped">
			<?php /* Extensions without a subscription. */ ?>
			<?php foreach ( $no_subscriptions as $filename => $data ) : ?>
				<?php
				$product_id = $data['_product_id'];
				$update_available = false;

				if ( ! empty( $updates[ $product_id ] ) &&
					version_compare( $updates[ $product_id ]['version'], $data['Version'], '>' ) ) {
					$update_available = true;
				}

				$product_url = '#';
				if ( ! empty( $updates[ $product_id ]['url'] ) ) {
					$product_url = $updates[ $product_id ]['url'];
				} elseif ( ! empty( $data['PluginURI'] ) ) {
					$product_url = $data['PluginURI'];
				}
				?>
				<tbody>
					<tr class="wp-list-table__row is-ext-header">
						<td class="wp-list-table__ext-details color-bar autorenews">
							<div class="wp-list-table__ext-title">
								<a href="<?php echo esc_url( $product_url ); ?>" target="_blank"><?php echo esc_html( $data['Name'] ); ?></a>
							</div>
							<div class="wp-list-table__ext-description">
							</div>
						</td>
						<td class="wp-list-table__ext-actions">
							<span class="form-toggle__wrapper">
								<span class="form-toggle disabled is-compact" ><?php _e( 'Inactive', 'woocommerce' ); ?></span>
								<label class="form-toggle__label" for="activate-extension">
									<span class="form-toggle__label-content">
										<label for="activate-extension"><?php _e( 'Inactive', 'woocommerce' ); ?></label>
									</span>
									<span class="form-toggle__switch"></span>
								</label>
							</span>
						</td>
					</tr>

					<?php if ( $update_available ) : ?>
					<tr class="wp-list-table__row wp-list-table__ext-updates">
						<td class="wp-list-table__ext-status update-available">
							<p><span class="dashicons dashicons-update"></span>
								<?php printf( __( 'Version %s is available. To enable this update you need to <strong>purchase</strong> a new subscription.', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?>
							</p>
						</td>
						<td class="wp-list-table__ext-actions">
							<a class="button" href="<?php echo esc_url( $product_url ); ?>" target="_blank"><?php _e( 'Purchase', 'woocommerce' ); ?></a>
						</td>
					</tr>
					<?php endif; ?>

				</tbody>

			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>
