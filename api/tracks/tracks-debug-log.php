<?php

/**
 * A class for logging tracked events.
 */
class TracksDebugLog {
	/**
	 * Logger class to use.
	 *
	 * @var WC_Logger_Interface|null
	 */
	private $logger;

	/**
	 * Logger source.
	 *
	 * @var string logger source.
	 */
	private $source = 'tracks';

	/**
	 * Initialize hooks.
	 */
	public function __construct() {
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'log_event' ), 10, 2 );
		$logger = wc_get_logger();
		$this->logger = $logger;
		$this->logger = $logger;
	}

	/**
	 * Log the event.
	 *
	 * @param array  $properties Event properties.
	 * @param string $event_name Event name.
	 */
	public function log_event( $properties, $event_name ) {
		$this->logger->debug(
			$event_name,
			array( 'source' => $this->source )
		);
		foreach ( $properties as $key => $property ) {
			$this->logger->debug(
				"  - {$key}: {$property}",
				array( 'source' => $this->source )
			);
		}

		return $properties;
	}
}

new TracksDebugLog();