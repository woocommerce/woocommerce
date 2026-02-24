<?php
/**
 * Display the Order Attribution details metabox.
 *
 * This template is used to display the order attribution data metabox on the edit order screen.
 *
 * @see     Automattic\WooCommerce\Internal\Orders\OrderAttributionController
 * @package WooCommerce\Templates
 * @version 10.3.0
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
		<?php if ( array_key_exists( 'source_type', $meta ) ) : ?>
			<h4><?php esc_html_e( 'Source type', 'woocommerce' ); ?></h4>
			<span class="order-attribution-source_type">
				<?php echo esc_html( $meta['source_type'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_campaign', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Campaign', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-campaign">
				<?php echo esc_html( $meta['utm_campaign'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_source', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Source', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-source">
				<?php echo esc_html( $meta['utm_source'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_medium', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Medium', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-medium">
				<?php echo esc_html( $meta['utm_medium'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_source_platform', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Source platform', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-source-platform">
				<?php echo esc_html( $meta['utm_source_platform'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_creative_format', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Creative format', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-creative-format">
				<?php echo esc_html( $meta['utm_creative_format'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_marketing_tactic', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Marketing tactic', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-marketing-tactic">
				<?php echo esc_html( $meta['utm_marketing_tactic'] ); ?>
			</span>
		<?php endif; ?>

		<?php if ( array_key_exists( 'utm_content', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Content', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-content">
				<?php echo esc_html( $meta['utm_content'] ); ?>
			</span>
		<?php endif; ?>
		
		<?php if ( array_key_exists( 'utm_term', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'Term', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-term">
				<?php echo esc_html( $meta['utm_term'] ); ?>
			</span>
		<?php endif; ?>
		
		<?php if ( array_key_exists( 'utm_id', $meta ) ) : ?>
			<h4>
				<?php esc_html_e( 'ID', 'woocommerce' ); ?>
			</h4>
			<span class="order-attribution-utm-id">
				<?php echo esc_html( $meta['utm_id'] ); ?>
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
	<!-- A placeholder for the OA install banner React component. -->
	<?php if ( class_exists( 'WC_Marketplace_Suggestions' ) && \WC_Marketplace_Suggestions::allow_suggestions() ) : ?>
		<div id="order-attribution-install-banner-slotfill"></div>
	<?php endif; ?>
</div>
