<?php

namespace Automattic\WooCommerce\Admin\PluginsInstallLoggers;

/**
 * A logger to log plugin installation progress in real time to an option.
 */
class AsyncPluginsInstallLogger implements PluginsInstallLogger {

	/**
	 * Variable to store logs.
	 *
	 * @var string $option_name option name to store logs.
	 */
	private $option_name;

	/**
	 * Constructor.
	 *
	 * @param string $option_name option name.
	 */
	public function __construct( string $option_name ) {
		$this->option_name = $option_name;
		add_option(
			$this->option_name,
			array(
				'created_time' => time(),
				'status'       => 'pending',
				'plugins'      => array(),
			),
			'',
			'no'
		);

		// Set status as failed in case we run out of exectuion time.
		register_shutdown_function(
			function () {
				$error = error_get_last();
				if ( isset( $error['type'] ) && E_ERROR === $error['type'] ) {
					$option           = $this->get();
					$option['status'] = 'failed';
					$this->update( $option );
				}
			}
		);
	}

	/**
	 * Update the option.
	 *
	 * @param array $data New data.
	 *
	 * @return bool
	 */
	private function update( array $data ) {
		return update_option( $this->option_name, $data );
	}

	/**
	 * Retreive the option.
	 *
	 * @return false|mixed|void
	 */
	private function get() {
		return get_option( $this->option_name );
	}

	/**
	 * Add requested plugin.
	 *
	 * @param string $plugin_name plugin name.
	 *
	 * @return void
	 */
	public function install_requested( string $plugin_name ) {
		$option = $this->get();
		if ( ! isset( $option['plugins'][ $plugin_name ] ) ) {
			$option['plugins'][ $plugin_name ] = array(
				'status'           => 'installing',
				'errors'           => array(),
				'install_duration' => 0,
			);
		}
		$this->update( $option );
	}

	/**
	 * Add installed plugin.
	 *
	 * @param string $plugin_name plugin name.
	 * @param int    $duration time took to install plugin.
	 *
	 * @return void
	 */
	public function installed( string $plugin_name, int $duration ) {
		$option = $this->get();

		$option['plugins'][ $plugin_name ]['status']           = 'installed';
		$option['plugins'][ $plugin_name ]['install_duration'] = $duration;
		$this->update( $option );
	}

	/**
	 * Change status to activated.
	 *
	 * @param string $plugin_name plugin name.
	 *
	 * @return void
	 */
	public function activated( string $plugin_name ) {
		$option = $this->get();

		$option['plugins'][ $plugin_name ]['status'] = 'activated';
		$this->update( $option );
	}

	/**
	 * Add an error.
	 *
	 * @param string      $plugin_name plugin name.
	 * @param string|null $error_message error message.
	 *
	 * @return void
	 */
	public function add_error( string $plugin_name, string $error_message = null ) {
		$option = $this->get();

		$option['plugins'][ $plugin_name ]['errors'][] = $error_message;
		$option['plugins'][ $plugin_name ]['status']   = 'failed';
		$option['status']                              = 'failed';

		wc_admin_record_tracks_event(
			'coreprofiler_store_extension_installed_and_activated',
			array(
				'success'       => false,
				'extension'     => $plugin_name,
				'error_message' => $error_message,
			)
		);

		$this->update( $option );
	}

	/**
	 * Record completed_time.
	 *
	 * @param array $data return data from install_plugins().
	 * @return void
	 */
	public function complete( $data = array() ) {
		$option = $this->get();

		$option['complete_time'] = time();
		$option['status']        = 'complete';

		$this->track( $data );
		$this->update( $option );
	}

	private function get_plugin_track_key( $id ) {
		$slug = explode( ':', $id )[0];
		$key  = preg_match( '/^woocommerce(-|_)payments$/', $slug )
			? 'wcpay'
			: explode( ':', str_replace( '-', '_', $slug ) )[0];
		return $key;
	}

	/**
	 * Returns time frame for a given time in milliseconds.
	 *
	 * @param int $timeInMs - time in milliseconds
	 *
	 * @return string - Time frame.
	 */
	function get_timeframe( $timeInMs ) {
		$time_frames = [
			[
				'name' => '0-2s',
				'max'  => 2,
			],
			[
				'name' => '2-5s',
				'max'  => 5,
			],
			[
				'name' => '5-10s',
				'max'  => 10,
			],
			[
				'name' => '10-15s',
				'max'  => 15,
			],
			[
				'name' => '15-20s',
				'max'  => 20,
			],
			[
				'name' => '20-30s',
				'max'  => 30,
			],
			[
				'name' => '30-60s',
				'max'  => 60,
			],
			[ 'name' => '>60s' ],
		];

		foreach ( $time_frames as $time_frame ) {
			if ( ! isset( $time_frame['max'] ) ) {
				return $time_frame['name'];
			}
			if ( $timeInMs < $time_frame['max'] * 1000 ) {
				return $time_frame['name'];
			}
		}
	}

	private function track( $data ) {
		$track_data = array(
			'success'              => true,
			'installed_extensions' => array_map(
				function( $extension ) {
					return $this->get_plugin_track_key( $extension );
				},
				$data['installed']
			),
			'total_time'           => $this->get_timeframe( ( time() - $data['start_time'] ) * 1000 ),
		);

		foreach ( $data['installed'] as $plugin ) {
			if ( ! isset( $data['time'][ $plugin ] ) ) {
				continue;
			}

			$plugin_track_key                                  = $this->get_plugin_track_key( $plugin );
			$install_time                                      = $this->get_timeframe( $data['time'][ $plugin ] );
			$track_data[ 'install_time_' . $plugin_track_key ] = $install_time;

			wc_admin_record_tracks_event(
				'coreprofiler_store_extension_installed_and_activated',
				array(
					'success'      => true,
					'extension'    => $plugin_track_key,
					'install_time' => $install_time,
				)
			);
		}

		wc_admin_record_tracks_event( 'coreprofiler_store_extensions_installed_and_activated', $track_data );
	}
}
