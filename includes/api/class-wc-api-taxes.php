<?php
/**
 * WooCommerce API Taxes Class
 *
 * Handles requests to the /taxes endpoint
 *
 * @author   WooThemes
 * @category API
 * @package  WooCommerce/API
 * @since    2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_API_Taxes extends WC_API_Resource {

	/** @var string $base the route base */
	protected $base = '/taxes';

	/**
	 * Register the routes for this class
	 *
	 * GET /taxes
	 * GET /taxes/count
	 * GET /taxes/<id>
	 *
	 * @since 2.1
	 * @param array $routes
	 * @return array
	 */
	public function register_routes( $routes ) {

		# GET/POST /taxes
		$routes[ $this->base ] = array(
			array( array( $this, 'get_taxes' ), WC_API_Server::READABLE ),
			array( array( $this, 'create_tax' ), WC_API_Server::CREATABLE | WC_API_Server::ACCEPT_DATA ),
		);

		# GET /taxes/count
		$routes[ $this->base . '/count'] = array(
			array( array( $this, 'get_taxes_count' ), WC_API_Server::READABLE ),
		);

		# GET/PUT/DELETE /taxes/<id>
		$routes[ $this->base . '/(?P<id>\d+)' ] = array(
			array( array( $this, 'get_tax' ), WC_API_Server::READABLE ),
			array( array( $this, 'edit_tax' ), WC_API_SERVER::EDITABLE | WC_API_SERVER::ACCEPT_DATA ),
			array( array( $this, 'delete_tax' ), WC_API_SERVER::DELETABLE ),
		);

		# POST|PUT /taxes/classes
		$routes[ $this->base . '/classes' ] = array(
			array( array( $this, 'get_tax_classes' ), WC_API_Server::READABLE ),
		);

		# POST|PUT /taxes/bulk
		$routes[ $this->base . '/bulk' ] = array(
			array( array( $this, 'bulk' ), WC_API_Server::EDITABLE | WC_API_Server::ACCEPT_DATA ),
		);

		return $routes;
	}

	/**
	 * Get all taxes
	 *
	 * @since 2.5.0
	 *
	 * @param string $fields
	 * @param array  $filter
	 * @param string $class
	 * @param int    $page
	 *
	 * @return array
	 */
	public function get_taxes( $fields = null, $filter = array(), $class = null, $page = 1 ) {
		if ( ! empty( $class ) ) {
			$filter['tax_class'] = $class;
		}

		$filter['page'] = $page;

		$query = $this->query_tax_rates( $filter );

		$taxes = array();

		foreach ( $query['results'] as $tax ) {
			$taxes[] = current( $this->get_tax( $tax->tax_rate_id, $fields ) );
		}

		// Set pagination headers
		$this->server->add_pagination_headers( $query['headers'] );

		return array( 'taxes' => $taxes );
	}

	/**
	 * Get the tax for the given ID
	 *
	 * @since 2.5.0
	 *
	 * @param int $id the tax ID
	 * @param string $fields fields to include in response
	 *
	 * @return array|WP_Error
	 */
	public function get_tax( $id, $fields = null ) {
		global $wpdb;

		try {
			$id = absint( $id );

			// Permissions check
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_tax_rate', __( 'You do not have permission to read tax rate', 'woocommerce' ), 401 );
			}

			// Get tax rate details
			$tax = $wpdb->get_row( $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->prefix}woocommerce_tax_rates
				WHERE tax_rate_id = %d
			", $id ) );

			if ( is_wp_error( $tax ) || is_null( $tax ) ) {
				throw new WC_API_Exception( 'woocommerce_api_invalid_tax_rate_id', __( 'A tax rate with the provided ID could not be found', 'woocommerce' ), 404 );
			}

			$tax_data = array(
				'id'       => $tax->tax_rate_id,
				'country'  => $tax->tax_rate_country,
				'state'    => $tax->tax_rate_state,
				'postcode' => '',
				'city'     => '',
				'rate'     => $tax->tax_rate,
				'name'     => $tax->tax_rate_name,
				'priority' => (int) $tax->tax_rate_priority,
				'compound' => (bool) $tax->tax_rate_compound,
				'shipping' => (bool) $tax->tax_rate_shipping,
				'order'    => (int) $tax->tax_rate_order,
				'class'    => $tax->tax_rate_class ? $tax->tax_rate_class : 'standard'
			);

			// Get locales from a tax rate
			$locales = $wpdb->get_results( $wpdb->prepare( "
				SELECT location_code, location_type
				FROM {$wpdb->prefix}woocommerce_tax_rate_locations
				WHERE tax_rate_id = %d
			", $id ) );

			if ( ! is_wp_error( $tax ) && ! is_null( $tax ) ) {
				foreach ( $locales as $locale ) {
					$tax_data[ $locale->location_type ] = $locale->location_code;
				}
			}

			return array( 'tax' => apply_filters( 'woocommerce_api_tax_response', $tax_data, $tax, $fields, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get all tax classes
	 *
	 * @since 2.5.0
	 *
	 * @param string $fields
	 *
	 * @return array
	 */
	public function get_tax_classes( $fields = null ) {
		try {
			// Permissions check
			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				throw new WC_API_Exception( 'woocommerce_api_user_cannot_read_tax_classes', __( 'You do not have permission to read tax classes', 'woocommerce' ), 401 );
			}

			$tax_classes = array();

			$classes = WC_Tax::get_tax_classes();

			foreach ( $classes as $class ) {
				$tax_classes[] = apply_filters( 'woocommerce_api_tax_class_response', array(
					'id'   => sanitize_title( $class ),
					'name' => $class
				), $class, $fields, $this );
			}

			return array( 'tax_classes' => apply_filters( 'woocommerce_api_tax_classes_response', $tax_classes, $classes, $fields, $this ) );
		} catch ( WC_API_Exception $e ) {
			return new WP_Error( $e->getErrorCode(), $e->getMessage(), array( 'status' => $e->getCode() ) );
		}
	}

	/**
	 * Get the total number of taxes
	 *
	 * @since 2.5.0
	 *
	 * @param array $filter
	 *
	 * @return array
	 */
	public function get_taxes_count( $filter = array() ) {

	}

	/**
	 * Create a tax
	 *
	 * @since 2.5.0
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function create_tax( $data ) {

	}

	/**
	 * Edit a tax
	 *
	 * @since 2.5.0
	 *
	 * @param int $id the tax ID
	 * @param array $data
	 *
	 * @return array
	 */
	public function edit_tax( $id, $data ) {

	}

	/**
	 * Delete a tax
	 *
	 * @since 2.5.0
	 *
	 * @param int $id the tax ID
	 * @param bool $force true to permanently delete tax, false to move to trash
	 *
	 * @return array
	 */
	public function delete_tax( $id, $force = false ) {

	}

	/**
	 * Helper method to get tax rates objects
	 *
	 * @since 2.5.0
	 *
	 * @param  array $args
	 *
	 * @return array
	 */
	protected function query_tax_rates( $args ) {
		global $wpdb;

		// Set args
		$args = $this->merge_query_args( $args, array() );

		$query = "
			SELECT tax_rate_id
			FROM {$wpdb->prefix}woocommerce_tax_rates
			WHERE 1 = 1
		";

		// Filter by tax class
		if ( ! empty( $args['tax_class'] ) ) {
			$tax_class = 'standard' !== $args['tax_class'] ? sanitize_title( $args['tax_class'] ) : '';
			$query .= " AND tax_rate_class = '$tax_class'";
		}

		// Order tax rates
		$order_by = ' ORDER BY tax_rate_order';

		// Pagination
		$per_page   = isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : get_option( 'posts_per_page' );
		$offset     = 1 < $args['paged'] ? ( $args['paged'] - 1 ) * $per_page : 0;
		$pagination = sprintf( ' LIMIT %d, %d', $offset, $per_page );

		$results = $wpdb->get_results( $query . $order_by . $pagination );

		$wpdb->get_results( $query );
		$headers              = new stdClass;
		$headers->page        = $args['paged'];
		$headers->total       = (int) $wpdb->num_rows;
		$headers->is_single   = $per_page > $headers->total;
		$headers->total_pages = ceil( $headers->total / $per_page );

		return array(
			'results' => $results,
			'headers' => $headers
		);
	}

	/**
	 * Bulk update or insert taxes
	 * Accepts an array with taxes in the formats supported by
	 * WC_API_Taxes->create_tax() and WC_API_Taxes->edit_tax()
	 *
	 * @since 2.5.0
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public function bulk( $data ) {

	}
}
