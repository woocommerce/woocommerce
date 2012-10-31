<?php
/**
 * Single product quantity inputs
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="quantity"><input name="<?php echo esc_attr( $input_name ); ?>" data-min="<?php echo esc_attr( $min_value ); ?>" data-max="<?php echo esc_attr( $max_value ); ?>" value="<?php echo esc_attr( $input_value ); ?>" size="4" title="Qty" class="input-text qty text" maxlength="12" /></div>