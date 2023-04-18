<?php
/**
 * Installation Manager
 *
 * @package WooCommerce\WCCom\Installation
 */

use WC_REST_WCCOM_Site_Installer_Error_Codes as Installer_Error_Codes;
use WC_REST_WCCOM_Site_Installer_Error as Installer_Error;

defined( 'ABSPATH' ) || exit;

class WC_WCCOM_Site_Installation_Manager {

	const STEPS = [
		'get_product_info',
		'download_product',
		'unpack_product',
		'move_product',
		'activate_product',
	];

	public function __construct( int $product_id, string $idempotency_key ) {
		$this->product_id      = $product_id;
		$this->idempotency_key = $idempotency_key;
	}

	public function run_installation( string $run_until_step ): bool {
		$state = WC_WCCOM_Site_Installation_State_Storage::get_state( $this->product_id );

		if ( $state && $state->get_idempotency_key() !== $this->idempotency_key ) {
			throw new Installer_Error( Installer_Error_Codes::IDEMPOTENCY_KEY_MISMATCH );
		}

		if ( ! $state ) {
			$state = WC_WCCOM_Site_Installation_State::initiate_new( $this->product_id, $this->idempotency_key );
		}

		$this->can_run_installation( $run_until_step, $state );

		$next_step          = $this->get_next_step( $state );
		$installation_steps = $this->get_installation_steps( $next_step, $run_until_step );

		array_walk( $installation_steps, function ( $step_name ) use ( $state ) {
			$this->run_step( $step_name, $state );
		} );

		return true;
	}

	public function reset_installation(): bool {
		$state = WC_WCCOM_Site_Installation_State_Storage::get_state( $this->product_id );

		if ( ! $state ) {
			throw new Installer_Error( Installer_Error_Codes::NO_INITIATED_INSTALLATION_FOUND );
		}

		if ( $state->get_idempotency_key() !== $this->idempotency_key ) {
			throw new Installer_Error( Installer_Error_Codes::IDEMPOTENCY_KEY_MISMATCH );
		}

		$result = WC_WCCOM_Site_Installation_State_Storage::delete_state( $state );
		if ( ! $result ) {
			throw new Installer_Error( Installer_Error_Codes::FAILED_TO_RESET_INSTALLATION_STATE );
		}

		return true;
	}

	protected function can_run_installation( $run_until_step, $state ) {
		//  if ( $this->already_installed( $product_id )) {
		//		throw new Installer_Error(Installer_Error_Codes::PLUGIN_ALREADY_INSTALLED_MESSAGE );
		//	}

		if ( $state->get_last_step_status() === \WC_WCCOM_Site_Installation_State::STEP_STATUS_IN_PROGRESS ) {
			throw new Installer_Error( Installer_Error_Codes::INSTALLATION_ALREADY_RUNNING );
		}

		if ( $state->get_last_step_status() === \WC_WCCOM_Site_Installation_State::STEP_STATUS_FAILED ) {
			throw new Installer_Error( Installer_Error_Codes::INSTALLATION_FAILED );
		}

		if ( $state->get_last_step_name() === self::STEPS[ count( self::STEPS ) - 1 ] ) {
			throw new Installer_Error( Installer_Error_Codes::ALL_INSTALLATION_STEPS_RUN );
		}

		if ( array_search( $state->get_last_step_name(), self::STEPS ) >= array_search( $run_until_step,
				self::STEPS ) ) {
			throw new Installer_Error( Installer_Error_Codes::REQUESTED_STEP_ALREADY_RUN );
		}

		if ( ! is_writable( WP_CONTENT_DIR ) ) {
			throw new Installer_Error( Installer_Error_Codes::FILESYSTEM_REQUIREMENTS_NOT_MET );
		}
	}

	protected function get_next_step( $state ): string {
		$last_executed_step = $state->get_last_step_name();

		if ( ! $last_executed_step ) {
			return self::STEPS[0];
		}

		$last_executed_step_index = array_search( $last_executed_step, self::STEPS );

		return self::STEPS[ $last_executed_step_index + 1 ];
	}

	protected function get_installation_steps( string $start_step, string $end_step ) {
		$start_step_offset = array_search( $start_step, self::STEPS );
		$end_step_index    = array_search( $end_step, self::STEPS );
		$length            = $end_step_index - $start_step_offset + 1;

		return array_slice( self::STEPS, $start_step_offset, $length );
	}


	protected function run_step( $step_name, $state ) {
		$state->initiate_step( $step_name );
		WC_WCCOM_Site_Installation_State_Storage::save_state( $state );

		try {
			$class_name   = "WC_WCCOM_Site_Installation_Step_$step_name";
			$current_step = new $class_name( $state );
			$current_step->run();
		} catch ( Installer_Error $exception ) {
			$state->capture_failure( $step_name, $exception->get_error_code() );
			WC_WCCOM_Site_Installation_State_Storage::save_state( $state );

			throw $exception;
		} catch ( Throwable $error ) {
			$state->capture_failure( $step_name, Installer_Error_Codes::UNEXPECTED_ERROR );
			WC_WCCOM_Site_Installation_State_Storage::save_state( $state );

			throw new Installer_Error( Installer_Error_Codes::UNEXPECTED_ERROR, $error->getMessage() );
		}

		$state->complete_step( $step_name );
		WC_WCCOM_Site_Installation_State_Storage::save_state( $state );
	}
}
