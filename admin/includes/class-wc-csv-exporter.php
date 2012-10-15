<?php

/**
 * WC_CSV_Exporter class.
 *
 * Takes any data and turns it into a CSV for output.
 *
 * @class 		WC_Cart
 * @version		1.6.4
 * @package		WooCommerce/Classes
 * @author 		WooThemes
 */
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class WC_CSV_Exporter {
	
	/**
	 * @var mixed
	 * @access private
	 */
	private $_csv = '';
	
	/**
	 * @var string
	 * @access private
	 */
	private $_filename = '';
	
	/**
	 * @var string
	 * @access private
	 */
	private $_output = true;

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param bool $output (default: true)
	 * @param mixed $filename	 
	 * @return void
	 */
	function __construct( $columns = array(), $output = true, $filename = '' ) {
		$this->_csv = '';
		$this->_filename = $filename ? $filename : 'export.csv';
		
		if ( $this->_output = $output )
			$this->start();
			
		if ( $columns )
			$this->set_columns( $columns );
	}
	
	function set_filename( $filename ) {
		$this->_filename = $filename ? $filename : 'export.csv';
	}
	
	/**
	 * set_columns function.
	 * 
	 * @access public
	 * @return void
	 */
	function set_columns( $columns = array() ) {
		$this->add_row( $columns );	
		unset( $columns );
	}
	
	/**
	 * add_row function.
	 * 
	 * @access public
	 * @return void
	 */
	function add_row( $row ) {
		
		$row = implode( ',', array_map( array( &$this, 'wrap_column' ), $row ) ) . "\n";
		
		if ( $this->_output ) 
			fwrite( $this->_csv, $row );
		else
			$this->_csv += $row;
			
		unset( $row );
	}
	
	/**
	 * start function.
	 * 
	 * @access public
	 * @return void
	 */
	function start() {
		if ( headers_sent() )
			wp_die( 'Headers already sent' );
			
		@set_time_limit(0);
		@ob_clean();
		
		header( "Content-Type: text/csv; charset=UTF-8" );
		header( "Content-Disposition: attachment; filename={$this->_filename}" );
		header( "Pragma: no-cache" );
		header( "Expires: 0" );
		
		$this->_csv = fopen( 'php://output', 'w') ;
	}
	
	/**
	 * end function.
	 * 
	 * @access public
	 * @return void
	 */
	function end() {
		fclose( $this->_csv );
		exit;
	}
	
	/**
	 * wrap_column function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	function wrap_column( $data ) {
		return '"' . str_replace( '"', '""', $data ) . '"';
	}
	
}