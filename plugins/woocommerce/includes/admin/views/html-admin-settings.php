<?php
/**
 * Admin View: Settings
 *
 * This file is included in WC_Admin_Settings::output().
 *
 * @package WooCommerce
 */

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

$hide_nav = FeaturesUtil::feature_is_enabled( 'reactify-classic-payments-settings' ) &&
	( 'checkout' === $current_tab && 'offline' === $current_section );
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
	</form>
	<?php do_action( 'woocommerce_after_settings_' . $current_tab ); ?>
</div>
