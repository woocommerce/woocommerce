<?php

namespace Automattic\WooCommerce\Admin\Features\Blueprint;

class StepProcessorResult {
	private $errors = array();
	private $success = false;
	public function __construct( $success ) {
		$this->success = $success;
	}

	public static function success() {
		return ( new self( true ) );
	}

	public function add_error( $message ) {
		$this->errors[] = $this->translate( $message );
	}

	public function translate( $message ) {
		return __( $message, 'woocommerce' );
	}

	public function get_errors() {
	    return $this->errors;
	}

	public function is_success() {
	    return $this->success === true;
	}
}
