<?php
/**
 * WooCommerce abstract privacy class.
 *
 * @package WooCommerce/Abstracts
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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
	public $privacy_name;

	/**
	 * This is a list of exporters.
	 *
	 * @var array
	 */
	protected $exporters;

	/**
	 * This is a list of erasers.
	 *
	 * @var array
	 */
	protected $erasers;

	/**
	 * Constructor
	 *
	 * @param string $privacy_name Privacy identifier
	 */
	public function __construct( $privacy_name = '' ) {
		$this->privacy_name = $privacy_name;
		$this->exporters    = array();
		$this->erasers      = array();

		add_action( 'admin_init', array( $this, 'add_privacy_message' ) );
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_erasers' ) );
	}

	/**
	 * Adds the privacy message on WC privacy page.
	 */
	public function add_privacy_message() {
		if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
			wp_add_privacy_policy_content( $this->privacy_name, $this->get_message() );
		}
	}

	/**
	 * Gets the message of the privacy to display.
	 * To be overloaded by the implementor.
	 *
	 * @return string
	 */
	abstract public function get_message();

	/**
	 * Integrate this exporter implementation within the WordPress core exporters.
	 *
	 * @param array $exporters List of exporter callbacks.
	 * @return array
	 */
	public function register_exporters( $exporters = array() ) {
		return array_merge( $exporters, $this->exporters );
	}

	/**
	 * Integrate this eraser implementation within the WordPress core erasers.
	 *
	 * @param array $erasers List of eraser callbacks.
	 * @return array
	 */
	public function register_erasers( $erasers = array() ) {
		return array_merge( $erasers, $this->erasers );
	}

	/**
	 * Add exporter to list of exporters.
	 *
	 * @param string $name Exporter name
	 * @param string $callback Exporter callback
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
	 * @param string $name Exporter name
	 * @param string $callback Exporter callback
	 */
	public function add_eraser( $name, $callback ) {
		$this->erasers[] = array(
			'eraser_friendly_name' => $name,
			'callback'             => $callback,
		);

		return $this->erasers;
	}

	/**
	 * Example implementation of an exporter.
	 *
	 * Plugins can add as many items in the item data array as they want. Example:
	 *
	 *     $data = array(
	 *       array(
	 *         'name'  => __( 'Commenter Latitude' ),
	 *         'value' => $latitude
	 *       ),
	 *       array(
	 *         'name'  => __( 'Commenter Longitude' ),
	 *         'value' => $longitude
	 *       )
	 *     );
	 *
	 *     $export_items[] = array(
	 *       'group_id'    => $group_id,
	 *       'group_label' => $group_label,
	 *       'item_id'     => $item_id,
	 *       'data'        => $data,
	 *     );
	 *   }
	 * }
	 *
	 * Tell core if we have more comments to work on still. Example:
	 * $done = count( $comments ) < $number;
	 *
	 * return array(
	 *   'data' => $export_items,
	 *   'done' => $done,
	 * );
	 *
	 * @param string $email_address E-mail address to export.
	 * @param int    $page          Pagination of data.
	 *
	 * @return array
	 */
	public final function example_exporter( $email_address, $page = 1 ) {}

	/**
	 * Example implementation of an eraser.
	 *
	 * Plugins can add as many items in the item data array as they want. Example:
	 *
	 *     $data = array(
	 *       array(
	 *         'name'  => __( 'Commenter Latitude' ),
	 *         'value' => $latitude
	 *       ),
	 *       array(
	 *         'name'  => __( 'Commenter Longitude' ),
	 *         'value' => $longitude
	 *       )
	 *     );
	 *
	 *     $export_items[] = array(
	 *       'group_id'    => $group_id,
	 *       'group_label' => $group_label,
	 *       'item_id'     => $item_id,
	 *       'data'        => $data,
	 *     );
	 *   }
	 * }
	 *
	 * Tell core if we have more comments to work on still. Example:
	 * $done = count( $comments ) < $number;
	 *
	 * return array(
	 *   'data' => $export_items,
	 *   'done' => $done,
	 * );
	 *
	 * @param string $email_address E-mail address to export.
	 * @param int    $page          Pagination of data.
	 *
	 * @return array
	 */
	public final function example_eraser( $email_address, $page = 1 ) {}
}
