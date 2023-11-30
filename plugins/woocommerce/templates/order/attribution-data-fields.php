<?php
/**
 * Display the Order Attribution Data metabox.
 *
 * This template is used to display the order source data metabox on the edit order screen.
 *
 * @see     Automattic\WooCommerce\Internal\Orders\OrderAttributionController
 * @package WooCommerce\Templates
 * @version 8.4.0
 */

declare( strict_types=1 );

defined( 'ABSPATH' ) || exit;

/**
 * Variables used in this file.
 *
 * @var array $meta Array of meta data.
 * @var bool  $has_more_details Whether to show the more details toggle.
 */
?>

<div class="order-attribution-metabox">

	<?php if ( array_key_exists( 'origin', $meta ) ) : ?>
		<h4><?php esc_html_e( 'Origin', 'woocommerce' ); ?></h4>
	<?php endif; ?>

	<div class="woocommerce-order-attribution-origin-container">

		<?php if ( array_key_exists( 'origin', $meta ) ) : ?>
			<span class="order-attribution-origin">
				<?php echo esc_html( $meta['origin'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( $has_more_details ) : ?>

			<a href="" class="woocommerce-order-attribution-details-toggle" aria-expanded="false">
				<span class="toggle-text show"><?php esc_html_e( 'Show details', 'woocommerce' ); ?></span>
				<span class="toggle-text hide" aria-hidden="true"><?php esc_html_e( 'Hide details', 'woocommerce' ); ?></span>
				<span class="toggle-indicator" aria-hidden="true"></span>
			</a>
		<?php endif; ?>

	</div>

	<div class="woocommerce-order-attribution-details-container closed">
		<?php if ( array_key_exists( 'type', $meta ) ) : ?>
			<h4><?php esc_html_e( 'Source type', 'woocommerce' ); ?></h4>
			<span class="order-attribution-source_type">
				<?php echo esc_html( $meta['type'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_campaign', $meta ) ) : ?>
			<h4><?php esc_html_e( 'UTM campaign', 'woocommerce' ); ?></h4>
			<span class="order-attribution-utm-campaign">
				<?php echo esc_html( $meta['utm_campaign'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_source', $meta ) ) : ?>
			<h4><?php esc_html_e( 'UTM source', 'woocommerce' ); ?></h4>
			<span class="order-attribution-utm-source">
				<?php echo esc_html( $meta['utm_source'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_medium', $meta ) ) : ?>
			<h4><?php esc_html_e( 'UTM medium', 'woocommerce' ); ?></h4>
			<span class="order-attribution-utm-medium">
				<?php echo esc_html( $meta['utm_medium'] ); ?>
			</span>
		<?php endif; ?>

	</div>

	<?php if ( array_key_exists( 'device_type', $meta ) ) : ?>
		<h4><?php esc_html_e( 'Device type', 'woocommerce' ); ?></h4>
		<span class="order-attribution-device_type">
			<?php echo esc_html( $meta['device_type'] ); ?>
		</span>
	<?php endif; ?>

	<?php if ( array_key_exists( 'session_pages', $meta ) ) : ?>
		<h4>
			<?php
			esc_html_e( 'Session page views', 'woocommerce' );
			echo wp_kses_post(
				wc_help_tip(
					__(
						'The number of unique pages viewed by the customer prior to this order.',
						'woocommerce'
					)
				)
			);
			?>
		</h4>
		<span class="order-attribution-utm-session-pages">
			<?php echo esc_html( $meta['session_pages'] ); ?>
		</span>
	<?php endif; ?>
</div>
