<?php
/**
 * Admin new order email (plain text)
 *
 * @author		WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version 	2.3.13
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo strtoupper( $heading ) . "\n\n";

foreach ( $fields as $field ) {
	
	if ( isset( $field['label'] ) && isset( $field['value'] ) && $field['value'] ) {
		
		echo $field['label']. ' : ' . $field['value'] . "\n";
		
	}
}
