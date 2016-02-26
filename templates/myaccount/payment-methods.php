<?php
/**
 * Payment methods
 *
 * Shows customer payment methods on the account page.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/payment-methods.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	http://docs.woothemes.com/document/template-structure/
 * @author  WooThemes
 * @package WooCommerce/Templates
 * @version 2.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$saved_methods = wc_get_customer_saved_methods_list( get_current_user_id() );
$has_methods   = (bool) $saved_methods;

wc_print_notices(); ?>

<?php wc_get_template( 'myaccount/navigation.php' ); ?>

<div class="my-account-content">

	<?php do_action( 'woocommerce_before_account_payment_methods', $has_methods ); ?>

	<?php if ( $has_methods ) : ?>

		<table class="shop_table shop_table_responsive account-payment-methods-table">
			<thead>
				<tr>
					<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
						<th class="payment-method-<?php echo esc_attr( $column_id ); ?>"><span class="nobr"><?php echo esc_html( $column_name ); ?></span></th>
					<?php endforeach; ?>
				</tr>
			</thead>
			<?php foreach ( $saved_methods as $method ) : ?>
				<tr class="method">
					<?php foreach ( wc_get_account_payment_methods_columns() as $column_id => $column_name ) : ?>
						<td class="payment-method-<?php echo esc_attr( $column_id ); ?>" data-title="<?php echo esc_attr( $column_name ); ?>">
							<?php
								// @TODO
							?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
		</table>

	<?php else : ?>

		<p><?php esc_html_e( 'No saved method found.', 'woocommerce' ); ?></p>

	<?php endif; ?>

	<?php do_action( 'woocommerce_after_account_payment_methods', $has_methods ); ?>

	<a class="button" href="<?php echo esc_url( wc_get_endpoint_url( 'add-payment-method' ) ); ?>"><?php esc_html_e( 'Add New Payment Method', 'woocommerce' ); ?></a>

</div>
