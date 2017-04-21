<?php defined( 'ABSPATH' ) or exit(); ?>

<div class="wrap wc-helper">
	<h1><?php _e( 'WooCommerce Helper', 'woocommerce' ); ?></h1>

	<?php include( WC_Helper::get_view_filename( 'html-section-notices.php' ) ); ?>
	<?php include( WC_Helper::get_view_filename( 'html-section-account.php' ) ); ?>

	<h2>Subscriptions</h2>
	<p>Below is a list of products available on your WooCommerce.com account. To receive plugin updates please make sure the product is installed, activated and connected to your WooCommerce.com account.</p>

	<table class="wp-list-table widefat fixed striped ">
		<thead>
			<tr>
				<th>Product</th>
				<th>Subscription</th>
				<th>Site usage</th>
				<th>Action</th>
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

					$classes = array_filter( $classes, function( $i ) { return (bool) $i; } );
					$classes = array_keys( $classes );
				?>
				<tr>
					<td class="<?php echo implode( ' ', $classes ); ?>">
						<a class="product-name" href="<?php echo esc_url( $subscription['product_url'] ); ?>" target="_blank"><?php
							echo esc_html( $subscription['product_name'] ); ?></a><br>
						<?php if ( $update_available && $connected && ! $subscription['expired'] ) : ?>
							<a href="<?php echo admin_url( 'update-core.php' ); ?>">Update to <?php echo esc_html( $updates[ $product_id ]['version'] ); ?></a>
						<?php endif; ?>

						<?php if ( $update_available && $connected && $subscription['expired'] ) : ?>
							<?php echo esc_html( $updates[ $product_id ]['version'] ); ?> available
							<a href="#">[?]</a><!-- Sub is active but expired -->
						<?php endif; ?>

						<?php if ( $update_available && ! $connected ) : ?>
							<?php echo esc_html( $updates[ $product_id ]['version'] ); ?> available
							<a href="#">[?]</a><!-- Subscription must be active to receive updates -->
						<?php endif; ?>

						<?php if ( ! $installed ) : ?>
							<strong><?php echo $updates[ $product_id ]['version']; ?> available</a></strong>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $subscription['expired'] ) : ?>
							<strong><span class="expired" style="color: #B81C23;">Expired :(</span></strong><br>
							<strong><?php echo date( 'F jS, Y', $subscription['expires'] ); ?></strong>
						<?php elseif ( $subscription['autorenew'] ) : ?>
							<span class="renews" style="color: black;">Auto renews on:</span><br>
							<span><?php echo date( 'F jS, Y', $subscription['expires'] ); ?></span>
						<?php elseif ( $subscription['expiring'] ) : ?>
							<strong><span class="expiring" style="color: orange;">Expiring soon!</span></strong><br>
							<strong><?php echo date( 'F jS, Y', $subscription['expires'] ); ?></strong>
						<?php else : ?>
							<span class="renews" style="color: black;">Expires on:</span><br>
							<span><?php echo date( 'F jS, Y', $subscription['expires'] ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<?php echo esc_html( $subscription['key_type_label'] ); ?>
						<?php
							if ( $subscription['sites_max'] > 0 ) {
								printf( '(%d of %d used)', $subscription['sites_active'], $subscription['sites_max'] );
							}
						?><br>
						<?php if ( ! $installed ) : ?>
							Not installed<br>
						<?php elseif ( $installed ) : ?>
							Installed on this site<br>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( ! $subscription['expired'] ) : ?>
							<?php if ( $connected ) : ?>
								<?php if ( $update_available ) : ?>
									<a class="button" href="<?php echo admin_url( 'update-core.php' ); ?>">Update</a>
								<?php endif; ?>
								<?php if ( $subscription['expiring'] ) : ?>
									<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/">Renew</a>
								<?php endif; ?>
								<a class="button" href="<?php echo esc_url( $subscription['deactivate_url'] ); ?>">Deactivate</a>
							<?php elseif ( $subscription['expiring'] ) : ?>
								<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/">Renew</a>
							<?php elseif ( $installed ) : ?>
								<a class="button" href="<?php echo esc_url( $subscription['activate_url'] ); ?>">Activate</a>
							<?php else : ?>
								<a class="button" href="<?php echo esc_url( $download_url ); ?>" target="_blank">Download</a>
							<?php endif; ?>
						<?php else : ?>
							<a class="button" href="https://woocommerce.com/my-account/my-subscriptions/" target="_blank">Renew</a>
						<?php endif; ?>
					</td>
				</tr>

			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td colspan="3"><em>Could not find any subscriptions on your WooCommerce.com account</td>
			</tr>
		<?php endif; ?>
	</table>

	<?php if ( ! empty( $no_subscriptions ) ) : ?>
		<h2>Installed Extensions Without a Subscription</h2>
		<table class="wp-list-table widefat fixed striped ">
			<thead>
				<tr>
					<th>Product</th>
					<th>Subscription</th>
					<th></th>
					<th>Action</th>
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
						<?php echo esc_html( $data['Name'] ); ?><br>
						<?php echo esc_html( $data['Version'] ); ?> installed
						<?php if ( ! $update_available ) : ?>
							(latest)
						<?php endif; ?>
						<br>

						<?php if ( $update_available ) : ?>
						<strong><?php echo esc_html( $updates[ $product_id ]['version'] ); ?> available</strong>
						<a href="#">[?]</a><!-- There must be an active subscription to receive updates -->
						<?php endif; ?>

					</td>
					<td>No/Invalid subscription</td>
					<td></td>
					<td>
						<a class="button" href="<?php echo esc_url( $product_url ); ?>" target="_blank">Purchase Subscription</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>
