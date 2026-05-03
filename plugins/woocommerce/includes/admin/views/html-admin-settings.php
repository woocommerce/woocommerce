<?php
/**
 * Admin View: Settings
 *
 * This file is included in WC_Admin_Settings::output().
 *
 * @package WooCommerce
 */

// phpcs:disable WooCommerce.Commenting.CommentHooks.MissingHookComment

use Automattic\WooCommerce\Admin\Features\Features;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The current WC admin settings tab ID.
 *
 * @var string $current_tab
 */

/**
 * The current WC admin settings section ID.
 *
 * @var string $current_section
 */

$tab_exists        = isset( $tabs[ $current_tab ] ) || has_action( 'woocommerce_sections_' . $current_tab ) || has_action( 'woocommerce_settings_' . $current_tab ) || has_action( 'woocommerce_settings_tabs_' . $current_tab );
$current_tab_label = isset( $tabs[ $current_tab ] ) ? $tabs[ $current_tab ] : '';

if ( ! $tab_exists ) {
	wp_safe_redirect( admin_url( 'admin.php?page=wc-settings' ) );
	exit;
}

$hide_nav = 'checkout' === $current_tab && in_array( $current_section, array( 'offline', 'bacs', 'cheque', 'cod' ), true );

// Move 'Advanced' to the last.
if ( array_key_exists( 'advanced', $tabs ) ) {
	$advanced = $tabs['advanced'];
	unset( $tabs['advanced'] );
	// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	$tabs['advanced'] = $advanced;
}

$marketplace_base_url = trailingslashit(
	esc_url_raw( apply_filters( 'woo_com_base_url', 'https://woocommerce.com/' ) )
) . 'product-category/woocommerce-extensions/';

$marketplace_links = array(
	'products' => array(
		'url'         => $marketplace_base_url . 'merchandising/',
		'is_external' => true,
		/* translators: %1$s: opening link tag, %2$s: closing link tag */
		'message'     => __( '%1$sExplore solutions%2$s that help highlight products and drive more sales.', 'woocommerce' ),
	),
	'tax'      => array(
		'url'         => $marketplace_base_url . 'operations/sales-tax-and-duties/',
		'is_external' => true,
		/* translators: %1$s: opening link tag, %2$s: closing link tag */
		'message'     => __( '%1$sExplore solutions%2$s that help with tax calculations, compliance, and regional requirements.', 'woocommerce' ),
	),
	'shipping' => array(
		'url'         => $marketplace_base_url . 'shipping-delivery-and-fulfillment/',
		'is_external' => true,
		/* translators: %1$s: opening link tag, %2$s: closing link tag */
		'message'     => __( '%1$sExplore solutions%2$s that enhance shipping, delivery, and fulfillment workflows.', 'woocommerce' ),
	),
	'account'  => array(
		'url'         => $marketplace_base_url . 'store-content-and-customizations/cart-and-checkout-features/',
		'is_external' => true,
		/* translators: %1$s: opening link tag, %2$s: closing link tag */
		'message'     => __( '%1$sExplore solutions%2$s that help customize cart and checkout flows.', 'woocommerce' ),
	),
	'email'    => array(
		'url'         => $marketplace_base_url . 'marketing-extensions/email-marketing-extensions/',
		'is_external' => true,
		/* translators: %1$s: opening link tag, %2$s: closing link tag */
		'message'     => __( '%1$sExplore solutions%2$s that help automate and improve customer email communication.', 'woocommerce' ),
	),
	'general'  => array(
		'url'         => admin_url( 'admin.php?page=wc-admin&path=%2Fextensions' ),
		'is_external' => false,
		/* translators: %1$s: opening link tag, %2$s: closing link tag */
		'message'     => __( '%1$sDiscover additional solutions%2$s to boost your business and expand what your store can do.', 'woocommerce' ),
	),
);

?>

<div class="wrap woocommerce">
	<?php do_action( 'woocommerce_before_settings_' . $current_tab ); ?>
	<form method="<?php echo esc_attr( apply_filters( 'woocommerce_settings_form_method_tab_' . $current_tab, 'post' ) ); ?>" id="mainform" action="" enctype="multipart/form-data">
		<?php if ( ! $hide_nav ) : ?>
			<nav class="nav-tab-wrapper woo-nav-tab-wrapper">
				<?php

				foreach ( $tabs as $slug => $label ) {
					echo '<a href="' . esc_html( admin_url( 'admin.php?page=wc-settings&tab=' . esc_attr( $slug ) ) ) . '" class="nav-tab ' . ( $current_tab === $slug ? 'nav-tab-active' : '' ) . '">' . esc_html( $label ) . '</a>';
				}

				/**
				 * Hook for adding additional settings tabs.
				 *
				 * @since 1.0.0
				 */
				do_action( 'woocommerce_settings_tabs' );

				?>
			</nav>
		<?php endif; ?>
			<h1 class="screen-reader-text"><?php echo esc_html( $current_tab_label ); ?></h1>
			<?php
				do_action( 'woocommerce_sections_' . $current_tab );

				WC_Admin_Settings::show_messages();

				do_action( 'woocommerce_settings_' . $current_tab );
				do_action( 'woocommerce_settings_tabs_' . $current_tab ); // @deprecated 3.4.0 hook.
			?>
			<p class="submit">
				<?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
					<button name="save" disabled class="woocommerce-save-button components-button is-primary" type="submit" value="<?php esc_attr_e( 'Save changes', 'woocommerce' ); ?>"><?php esc_html_e( 'Save changes', 'woocommerce' ); ?></button>
				<?php endif; ?>
				<?php wp_nonce_field( 'woocommerce-settings' ); ?>
			</p>
			<?php if ( isset( $marketplace_links[ $current_tab ] ) ) : ?>
				<?php
				$link_config = $marketplace_links[ $current_tab ];

				if ( $link_config['is_external'] ) {
					$utm_source    = 'settings_' . $current_tab . ( $current_section ? '_' . $current_section : '' );
					$link_url      = add_query_arg( 'utm_source', $utm_source, $link_config['url'] );
					$icon_url      = WC()->plugin_url() . '/assets/images/icons/external-link.svg';
					$external_icon = '<img src="' . esc_url( $icon_url ) . '" alt="" />';
					$screen_reader = '<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'woocommerce' ) . '</span>';
					$link_open     = '<a href="' . esc_url( $link_url ) . '" target="_blank" rel="noopener noreferrer">' . $external_icon;
					$link_close    = $screen_reader . '</a>';
				} else {
					$link_open  = '<a href="' . esc_url( $link_config['url'] ) . '">';
					$link_close = '</a>';
				}
				?>
			<p class="wc-settings-marketplace-link" data-settings-tab="<?php echo esc_attr( $current_tab ); ?>"<?php echo $current_section ? ' data-settings-section="' . esc_attr( $current_section ) . '"' : ''; ?>>
				<?php
				echo wp_kses(
					sprintf( $link_config['message'], $link_open, $link_close ),
					array(
						'a'    => array(
							'href'   => array(),
							'target' => array(),
							'rel'    => array(),
						),
						'img'  => array(
							'src' => array(),
							'alt' => array(),
						),
						'span' => array(
							'class' => array(),
						),
					)
				);
				?>
			</p>
			<?php endif; ?>
	</form>
	<?php do_action( 'woocommerce_after_settings_' . $current_tab ); ?>
</div>
