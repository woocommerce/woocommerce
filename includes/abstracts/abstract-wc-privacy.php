<?php
/**
 * WooCommerce abstract privacy class.
 *
 * @since 3.4.0
 * @package WooCommerce/Abstracts
 */

defined( 'ABSPATH' ) || exit;

/**
 * Abstract class that is intended to be extended by
 * specific privacy class. It handles the display
 * of the privacy message of the privacy id to the admin,
 * privacy data to be exported and privacy data to be deleted.
 *
 * @version  3.4.0
 * @package  WooCommerce/Abstracts
 */
abstract class WC_Abstract_Privacy {
	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * This is a list of exporters.
	 *
	 * @var array
	 */
	protected $exporters = array();

	/**
	 * This is a list of erasers.
	 *
	 * @var array
	 */
	protected $erasers = array();

	/**
	 * Track ordering for adding export/erase to WP array.
	 *
	 * @var int
	 */
	protected $count = 0;

	/**
	 * Constructor
	 *
	 * @param string $name Plugin identifier.
	 */
	public function __construct( $name = '' ) {
		$this->name = $name;
		$this->init();
	}

	/**
	 * Get next key to be added to the exporters/erasures list.
	 * Maintain order since the array is associative.
	 *
	 * @return string
	 */
	protected function get_next_key() {
		$this->count++;
		return sanitize_title( $this->name . '-' . $this->count );
	}

	/**
	 * Hook in events.
	 */
	protected function init() {
		add_action( 'admin_init', array( $this, 'add_privacy_message' ) );
		// We set priority to 5 to help WooCommerce's findings appear before those from extensions in exported items.
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ), 5 );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ) );
	}

	/**
	 * Adds the privacy message on WC privacy page.
	 */
	public function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			$content = $this->get_privacy_message();

			if ( $content ) {
				wp_add_privacy_policy_content( $this->name, $this->get_privacy_message() );
			}
		}
	}

	/**
	 * Gets the message of the privacy to display.
	 * To be overloaded by the implementor.
	 *
	 * @return string
	 */
	public function get_privacy_message() {
		return '';
	}

	/**
	 * Integrate this exporter implementation within the WordPress core exporters.
	 *
	 * @param array $exporters List of exporter callbacks.
	 * @return array
	 */
	public function register_exporters( $exporters = array() ) {
		foreach ( $this->exporters as $exporter ) {
			$exporters[ $this->get_next_key() ] = $exporter;
		}
		return $exporters;
	}

	/**
	 * Integrate this eraser implementation within the WordPress core erasers.
	 *
	 * @param array $erasers List of eraser callbacks.
	 * @return array
	 */
	public function register_erasers( $erasers = array() ) {
		foreach ( $this->erasers as $eraser ) {
			$erasers[ $this->get_next_key() ] = $eraser;
		}
		return $erasers;
	}

	/**
	 * Add exporter to list of exporters.
	 *
	 * @param string $name Exporter name.
	 * @param string $callback Exporter callback.
	 */
	public function add_exporter( $name, $callback ) {
		$this->exporters[] = array(
			'exporter_friendly_name' => $name,
			'callback'               => $callback,
		);
		return $this->exporters;
	}

	/**
	 * Add eraser to list of exporters.
	 *
	 * @param string $name Exporter name.
	 * @param string $callback Exporter callback.
	 */
	public function add_eraser( $name, $callback ) {
		$this->erasers[] = array(
			'eraser_friendly_name' => $name,
			'callback'             => $callback,
		);
		return $this->erasers;
	}
}
