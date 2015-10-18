<?php
/**
 * Admin customer details template part
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.3.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>

<h2><?php echo $heading; ?></h2>

<?php
foreach ( $fields as $field ) :
	if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) : ?>

<p><strong><?php echo $field['label']; ?></strong><?php echo $field['value']; ?></p>

<?php endif;
endforeach; ?>
