<?php
/**
 * Email pickup locations
 *
 * Shows the pickup locations the customer chose at checkout.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-pickup-locations.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates\Emails
 * @version 11.3.0
 *
 * @var array<int, array{name: string, address: string, details: string}> $pickup_locations Pickup locations.
 */

defined( 'ABSPATH' ) || exit;

if ( empty( $pickup_locations ) ) {
	return;
}
?>
<div class="email-pickup-locations" style="margin-bottom: 24px;">
	<h2><?php echo esc_html( _n( 'Pickup location', 'Pickup locations', count( $pickup_locations ), 'woocommerce' ) ); ?></h2>
	<?php foreach ( $pickup_locations as $pickup_location ) : ?>
		<address class="address" style="margin-bottom: 12px;">
			<strong><?php echo esc_html( $pickup_location['name'] ); ?></strong>
			<?php if ( '' !== $pickup_location['address'] ) : ?>
				<br/><?php echo esc_html( $pickup_location['address'] ); ?>
			<?php endif; ?>
		</address>
		<?php if ( '' !== $pickup_location['details'] ) : ?>
			<?php echo wp_kses_post( wpautop( $pickup_location['details'] ) ); ?>
		<?php endif; ?>
	<?php endforeach; ?>
</div>
