<?php
/**
 * Admin View: Page - Status
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$current_tab = ! empty( $_REQUEST['tab'] ) ? sanitize_title( $_REQUEST['tab'] ) : 'status';
$tabs        = array(
	'status' => __( 'System Status', 'woocommerce' ),
	'tools'  => __( 'Tools', 'woocommerce' ),
	'logs'   => __( 'Logs', 'woocommerce' ),
);
?>
<div class="wrap woocommerce">
	<div class="icon32 icon32-woocommerce-status" id="icon-woocommerce"><br /></div>

	<h1><?php echo esc_html( $tabs[ $current_tab ] ); ?></h1>

	<p class="nav-tab-wrapper woo-nav-tab-wrapper">
		<?php
			foreach ( $tabs as $name => $label ) {
				echo '<a href="' . admin_url( 'admin.php?page=wc-status&tab=' . $name ) . '" class="nav-tab ';
				if ( $current_tab == $name ) echo 'nav-tab-active';
				echo '">' . $label . '</a>';
			}
		?>
	</p>
	<?php
		switch ( $current_tab ) {
			case "tools" :
				WC_Admin_Status::status_tools();
			break;
			case "logs" :
				WC_Admin_Status::status_logs();
			break;
			default :
				WC_Admin_Status::status_report();
			break;
		}
	?>
</div>
