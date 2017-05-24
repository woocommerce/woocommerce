<?php defined( 'ABSPATH' ) or exit(); ?>

<div class="wrap wc-helper">
	<h1><?php _e( 'WooCommerce Helper', 'woocommerce' ); ?></h1>

	<?php include( WC_Helper::get_view_filename( 'html-section-notices.php' ) ); ?>
	<?php include( WC_Helper::get_view_filename( 'html-section-account.php' ) ); ?>

	<h2><?php _e( 'Subscriptions', 'woocommerce' ); ?></h2>
	<p><?php _e( 'Below is a list of products available on your WooCommerce.com account. To receive plugin updates please make sure the product is installed, activated and connected to your WooCommerce.com account.', 'woocommerce' ); ?></p>

	<table class="wp-list-table widefat fixed striped ">
		<thead>
			<tr>
				<th><?php _e( 'Product', 'woocommerce' ); ?></th>
				<th><?php _e( 'Subscription', 'woocommerce' ); ?></th>
				<th><?php _e( 'Site Usage', 'woocommerce' ); ?></th>
				<th><?php _e( 'Action', 'woocommerce' ); ?></th>
			</tr>
		</thead>
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
				<tr>
					<td class="<?php echo implode( ' ', $classes ); ?>">
						<a class="product-name" href="<?php echo esc_url( $subscription['product_url'] ); ?>" target="_blank"><?php
							echo esc_html( $subscription['product_name'] ); ?></a><br>
						<?php if ( $update_available && $connected && ! $subscription['expired'] ) : ?>
							<?php /* translators: %s: version number */ ?>
							<a href="<?php echo admin_url( 'update-core.php' ); ?>"><?php printf( __( 'Update to %s', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?></a>
						<?php endif; ?>

						<?php if ( $update_available && $connected && $subscription['expired'] ) : ?>
							<?php /* translators: %s: version number */ ?>
							<?php printf( __( '%s available', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?>
							<a href="#">[?]</a><!-- Sub is active but expired -->
						<?php endif; ?>

						<?php if ( $update_available && ! $connected ) : ?>
							<?php /* translators: %s: version number */ ?>
							<?php printf( __( '%s available', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?>
							<a href="#">[?]</a><!-- Subscription must be active to receive updates -->
						<?php endif; ?>

						<?php if ( ! $installed ) : ?>
							<?php /* translators: %s: version number */ ?>
							<strong><?php printf( __( '%s available', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?></strong>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $subscription['expired'] ) : ?>
							<strong><span class="expired" style="color: #B81C23;"><?php _e( 'Expired :(', 'woocommerce' ); ?></span></strong><br>
							<strong><?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?></strong>
						<?php elseif ( $subscription['autorenew'] ) : ?>
							<span class="renews" style="color: black;"><?php _e( 'Auto renews on:', 'woocommerce' ); ?></span><br>
							<span><?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?></span>
						<?php elseif ( $subscription['expiring'] ) : ?>
							<strong><span class="expiring" style="color: orange;"><?php _e( 'Expiring soon!', 'woocommerce' ); ?></span></strong><br>
							<strong><?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?></strong>
						<?php else : ?>
							<span class="renews" style="color: black;"><?php _e( 'Expires on:', 'woocommerce' ); ?></span><br>
							<span><?php echo date_i18n( 'F jS, Y', $subscription['expires'] ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php echo esc_html( $subscription['key_type_label'] ); ?>
						<?php
							if ( $subscription['sites_max'] > 0 ) {
								/* translators: %1$d: sites active, %2$d max sites active */
								printf( __( '(%1$d of %2$d used)', 'woocommerce' ), $subscription['sites_active'], $subscription['sites_max'] );
							}
						?><br>
						<?php if ( ! $installed ) : ?>
							<?php _e( 'Not installed', 'woocommerce' ); ?><br>
						<?php elseif ( $installed ) : ?>
							<?php _e( 'Installed on this site', 'woocommerce' ); ?><br>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! $subscription['expired'] ) : ?>
							<?php if ( $connected ) : ?>
								<?php if ( $update_available ) : ?>
									<a class="button" href="<?php echo admin_url( 'update-core.php' ); ?>"><?php _e( 'Update', 'woocommerce' ); ?></a>
								<?php endif; ?>
								<?php if ( $subscription['expiring'] ) : ?>
									<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/"><?php _e( 'Renew', 'woocommerce' ); ?></a>
								<?php endif; ?>
								<a class="button" href="<?php echo esc_url( $subscription['deactivate_url'] ); ?>"><?php _e( 'Deactivate', 'woocommerce' ); ?></a>
							<?php elseif ( $subscription['expiring'] ) : ?>
								<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/"><?php _e( 'Renew', 'woocommerce' ); ?></a>
							<?php elseif ( $installed ) : ?>
								<a class="button" href="<?php echo esc_url( $subscription['activate_url'] ); ?>"><?php _e( 'Activate', 'woocommerce' ); ?></a>
							<?php else : ?>
								<a class="button" href="<?php echo esc_url( $download_url ); ?>" target="_blank"><?php _e( 'Download', 'woocommerce' ); ?></a>
							<?php endif; ?>
						<?php else : ?>
							<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/" target="_blank"><?php _e( 'Renew', 'woocommerce' ); ?></a>
						<?php endif; ?>
					</td>
				</tr>

			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="3"><em><?php _e( 'Could not find any subscriptions on your WooCommerce.com account', 'woocommerce' ); ?></td>
			</tr>
		<?php endif; ?>
	</table>

	<?php if ( ! empty( $no_subscriptions ) ) : ?>
		<h2><?php _e( 'Installed Extensions Without a Subscription', 'woocommerce' ); ?></h2>
		<table class="wp-list-table widefat fixed striped ">
			<thead>
				<tr>
					<th><?php _e( 'Product', 'woocommerce' ); ?></th>
					<th><?php _e( 'Subscription', 'woocommerce' ); ?></th>
					<th></th>
					<th><?php _e( 'Action', 'woocommerce' ); ?></th>
				</tr>
			</thead>

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
				<tr>
					<td>
						<?php /* translators: %1$s: product name, %2$s: product version */ ?>
						<?php printf( __( '%1$s<br>%2$s installed', 'woocommerce' ), esc_html( $data['Name'] ), esc_html( $data['Version'] ) ); ?>
						<?php if ( ! $update_available ) : ?>
							<?php _e( '(latest)', 'woocommerce' ); ?>
						<?php endif; ?>
						<br>

						<?php if ( $update_available ) : ?>
						<?php /* translators: %s: version number */ ?>
						<strong><?php printf( __( '%s available', 'woocommerce' ), esc_html( $updates[ $product_id ]['version'] ) ); ?></strong>
						<a href="#">[?]</a><!-- There must be an active subscription to receive updates -->
						<?php endif; ?>

					</td>
					<td><?php _e( 'No/Invalid subscription', 'woocommerce' ); ?></td>
					<td></td>
					<td>
						<a class="button" href="<?php echo esc_url( $product_url ); ?>" target="_blank"><?php _e( 'Purchase Subscription', 'woocommerce' ); ?></a>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>
