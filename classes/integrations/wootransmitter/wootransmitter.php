<?php
if ( ! class_exists( 'WooThemes_Transmitter' ) ) {
	require_once( 'classes/wootransmitter.class.php' );
	
	$GLOBALS['wootransmitter'] = new WooThemes_Transmitter( __FILE__ );
}