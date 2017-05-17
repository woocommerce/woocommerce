<?php
/**
 * Admin View: Importer - CSV import progress
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap woocommerce">
	<h1><?php esc_html_e( 'Importing', 'woocommerce' ); ?></h1>

	<div class="woocommerce-importer-wrapper">
		<form class="woocommerce-importer">
			<progress class="woocommerce-importer-progress" max="100" value="0"></progress>
		</form>
	</div>
</div>
