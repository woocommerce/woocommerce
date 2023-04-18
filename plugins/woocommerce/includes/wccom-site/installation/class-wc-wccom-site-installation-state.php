<?php
/**
 * State for the WCCOM Site installation process.
 *
 * @package WooCommerce\WCCOM\Installation
 */

defined( 'ABSPATH' ) || exit;

class WC_WCCOM_Site_Installation_State {
	protected $product_id;
	protected $idempotency_key;
	protected $last_step_name;
	protected $last_step_status;
	protected $last_step_error;

	protected $product_type;
	protected $product_name;
	protected $download_url;
	protected $download_path;
	protected $unpacked_path;
	protected $installed_path;
	protected $already_installed_plugin_info;

	protected $started_date;
	const STEP_STATUS_IN_PROGRESS = 'in-progress';
	const STEP_STATUS_FAILED = 'failed';
	const STEP_STATUS_COMPLETED = 'completed';

	protected function __construct( $product_id ) {
		$this->product_id = $product_id;
	}

	public static function initiate_existing( $product_id, $idempotency_key, $last_step_name, $last_step_status, $last_step_error, $started_date ) {
		$instance = new self( $product_id );
		$instance->idempotency_key = $idempotency_key;
		$instance->last_step_name = $last_step_name;
		$instance->last_step_status = $last_step_status;
		$instance->last_step_error = $last_step_error;
		$instance->started_date = $started_date;

		return $instance;
	}

	public static function initiate_new( $product_id, $idempotency_key ) {
		$instance = new self( $product_id );
		$instance->idempotency_key = $idempotency_key;
		$instance->started_date = time();

		return $instance;
	}

	public function get_product_id() {
		return $this->product_id;
	}

	public function get_idempotency_key() {
		return $this->idempotency_key;
	}

	public function get_last_step_name() {
		return $this->last_step_name;
	}

	public function get_last_step_status( ) {
		return $this->last_step_status;
	}

	public function get_last_step_error( ) {
		return $this->last_step_error;
	}

	public function initiate_step( $step_name ) {
		$this->last_step_name = $step_name;
		$this->last_step_status = self::STEP_STATUS_IN_PROGRESS;
	}

	public function complete_step( $step_name ) {
		$this->last_step_name = $step_name;
		$this->last_step_status = self::STEP_STATUS_COMPLETED;
	}
	public function capture_failure( $step_name, $error_code ) {
		$this->last_step_name = $step_name;
		$this->last_step_error = $error_code;
		$this->last_step_status = self::STEP_STATUS_FAILED;
	}

	public function get_product_type() {
		return $this->product_type;
	}
	public function set_product_type( $product_type ) {
		$this->product_type = $product_type;
	}

	public function get_product_name() {
		return $this->product_name;
	}

	public function set_product_name( $product_name ) {
		$this->product_name = $product_name;
	}

	public function get_download_url() {
		return $this->download_url;
	}

	public function set_download_url( $download_url ) {
		$this->download_url = $download_url;
	}

	public function get_download_path(  ) {
		return $this->download_path;
	}

	public function set_download_path( $download_path ) {
		$this->download_path = $download_path;
	}

	public function get_unpacked_path() {
		return $this->unpacked_path;
	}

	public function set_unpacked_path( $unpacked_path ) {
		$this->unpacked_path = $unpacked_path;
	}

	public function get_installed_path(  ) {
		return $this->installed_path;
	}

	public function set_installed_path( $installed_path ) {
		$this->installed_path = $installed_path;
	}

	public function get_already_installed_plugin_info() {
		return $this->already_installed_plugin_info;
	}

	public function set_already_installed_plugin_info( $plugin_info ) {
		$this->already_installed_plugin_info = $plugin_info;
	}

	public function get_started_date() {
		return $this->started_date;
	}
}
