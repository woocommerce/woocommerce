<?php
/**
 * Functions used for displaying reports in admin
 *
 * @author 		WooThemes
 * @category 	Admin
 * @package 	WooCommerce
 */

function woocommerce_reports() {

	$current_tab = (isset($_GET['tab'])) ? $_GET['tab'] : 'orders';
    ?>
	<div class="wrap woocommerce">
		<div class="icon32 icon32-woocommerce-settings" id="icon-woocommerce"><br></div><h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
			<?php
				$tabs = array(
					'orders' => __('Orders', 'woothemes'),
					'products' => __('Products', 'woothemes'),
					'customers' => __('Customers', 'woothemes')
				);
				foreach ($tabs as $name => $label) :
					echo '<a href="'.admin_url('admin.php?page=woocommerce_reports&tab='.$name).'" class="nav-tab ';
					if($current_tab==$name) echo 'nav-tab-active';
					echo '">'.$label.'</a>';
				endforeach;
			?>
			<?php do_action('woocommerce_reports_tabs'); ?>
		</h2>
	</div>
	<?php
}