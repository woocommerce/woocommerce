<?php
/**
 * My Addresses
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/myaccount/my-address.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 11.4.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing'  => __( 'Billing address', 'woocommerce' ),
			'shipping' => __( 'Shipping address', 'woocommerce' ),
		),
		$customer_id
	);
} else {
	$get_addresses = apply_filters(
		'woocommerce_my_account_get_addresses',
		array(
			'billing' => __( 'Billing address', 'woocommerce' ),
		),
		$customer_id
	);
}

$oldcol = 1;
$col    = 1;
?>

<p>
	<?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html__( 'The following addresses will be used on the checkout page by default.', 'woocommerce' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</p>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	<div class="u-columns woocommerce-Addresses col2-set addresses">
<?php endif; ?>

<?php
$formatted_addresses = array();
foreach ( array_keys( $get_addresses ) as $name ) {
	$formatted_addresses[ $name ] = wc_get_account_formatted_address( $name );
}
$copyable_from = array(
	'billing'  => 'shipping',
	'shipping' => 'billing',
);

// Whole-sentence link labels for the core address titles, so translators never
// have to fit a capitalized heading into "Add %s". Filtered titles fall back below.
$core_link_labels = array(
	'billing'  => array(
		'title' => __( 'Billing address', 'woocommerce' ),
		'add'   => __( 'Add billing address', 'woocommerce' ),
		'edit'  => __( 'Edit billing address', 'woocommerce' ),
		'copy'  => __( 'Copy billing address', 'woocommerce' ),
	),
	'shipping' => array(
		'title' => __( 'Shipping address', 'woocommerce' ),
		'add'   => __( 'Add shipping address', 'woocommerce' ),
		'edit'  => __( 'Edit shipping address', 'woocommerce' ),
		'copy'  => __( 'Copy shipping address', 'woocommerce' ),
	),
);
?>

<?php foreach ( $get_addresses as $name => $address_title ) : ?>
	<?php
		$address     = $formatted_addresses[ $name ];
		$copy_source = $copyable_from[ $name ] ?? '';
		$can_copy    = ! $address && $copy_source && ! empty( $formatted_addresses[ $copy_source ] );
		$col         = $col * -1;
		$oldcol      = $oldcol * -1;

		$has_core_title = isset( $core_link_labels[ $name ] ) && $core_link_labels[ $name ]['title'] === $address_title;
		/* translators: %s: Address title */
		$generic_label = $address ? __( 'Edit %s', 'woocommerce' ) : __( 'Add %s', 'woocommerce' );
		$edit_label    = $has_core_title ? $core_link_labels[ $name ][ $address ? 'edit' : 'add' ] : sprintf( $generic_label, $address_title );

		$copy_title    = $can_copy ? $get_addresses[ $copy_source ] : '';
		$has_core_copy = $can_copy && isset( $core_link_labels[ $copy_source ] ) && $core_link_labels[ $copy_source ]['title'] === $copy_title;
		/* translators: %s: Address title of the address being copied, e.g. "Billing address" */
		$copy_label = $has_core_copy ? $core_link_labels[ $copy_source ]['copy'] : sprintf( __( 'Copy %s', 'woocommerce' ), $copy_title );
	?>

	<div class="u-column<?php echo $col < 0 ? 1 : 2; ?> col-<?php echo $oldcol < 0 ? 1 : 2; ?> woocommerce-Address">
		<header class="woocommerce-Address-title title">
			<h2><?php echo esc_html( $address_title ); ?></h2>
			<a href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $name ) ); ?>" class="edit">
				<?php echo esc_html( $edit_label ); ?>
			</a>
		</header>
		<?php if ( $can_copy ) : ?>
			<p class="woocommerce-Address-copy">
				<a href="<?php echo esc_url( WC_Form_Handler::get_copy_address_url( $name ) ); ?>" class="copy no-prefetch" rel="nofollow">
					<?php echo esc_html( $copy_label ); ?>
				</a>
			</p>
		<?php endif; ?>
		<address>
			<?php
				echo $address ? wp_kses_post( $address ) : esc_html_e( 'You have not set up this type of address yet.', 'woocommerce' );

				/**
				 * Used to output content after core address fields.
				 *
				 * @param string $name Address type.
				 * @since 8.7.0
				 */
				do_action( 'woocommerce_my_account_after_my_address', $name );
			?>
		</address>
	</div>

<?php endforeach; ?>

<?php if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) : ?>
	</div>
	<?php
endif;
