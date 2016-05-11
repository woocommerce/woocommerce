<?php

/**
 * Manage Taxes.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Tax extends WC_CLI_Command {

	/**
	 * Create a tax rate.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new tax rate.
	 *
	 * [--porcelain]
	 * : Outputs just the new tax rate id.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for create command:
	 *
	 * * country
	 * * state
	 * * postcode
	 * * city
	 * * rate
	 * * name
	 * * priority
	 * * compound
	 * * shipping
	 * * class
	 * * order
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax create --country=US --rate=5 --class=standard --type=percent
	 *
	 * @since 2.5.0
	 */
	public function create( $__, $assoc_args ) {
		$porcelain = isset( $assoc_args['porcelain'] );
		unset( $assoc_args['porcelain'] );

		$assoc_args = apply_filters( 'woocommerce_cli_create_tax_rate_data', $assoc_args );

		$tax_data = array(
			'tax_rate_country'  => '',
			'tax_rate_state'    => '',
			'tax_rate'          => '',
			'tax_rate_name'     => '',
			'tax_rate_priority' => 1,
			'tax_rate_compound' => 0,
			'tax_rate_shipping' => 1,
			'tax_rate_order'    => 0,
			'tax_rate_class'    => '',
		);

		foreach ( $tax_data as $key => $value ) {
			$new_key = str_replace( 'tax_rate_', '', $key );
			$new_key = 'tax_rate' === $new_key ? 'rate' : $new_key;

			if ( isset( $assoc_args[ $new_key ] ) ) {
				if ( in_array( $new_key, array( 'compound', 'shipping' ) ) ) {
					$tax_data[ $key ] = $assoc_args[ $new_key ] ? 1 : 0;
				} else {
					$tax_data[ $key ] = $assoc_args[ $new_key ];
				}
			}
		}

		// Create tax rate.
		$id = WC_Tax::_insert_tax_rate( $tax_data );

		// Add locales.
		if ( ! empty( $assoc_args['postcode'] ) ) {
			WC_Tax::_update_tax_rate_postcodes( $id, wc_clean( $assoc_args['postcode'] ) );
		}

		if ( ! empty( $assoc_args['city'] ) ) {
			WC_Tax::_update_tax_rate_cities( $id, wc_clean( $assoc_args['city'] ) );
		}

		do_action( 'woocommerce_cli_create_tax_rate', $id, $tax_data );

		if ( $porcelain ) {
			WP_CLI::line( $id );
		} else {
			WP_CLI::success( "Created tax rate $id." );
		}
	}

	/**
	 * Create a tax class.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new tax class.
	 *
	 * [--porcelain]
	 * : Outputs just the new tax class slug.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for create command:
	 *
	 * * name
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax create_class --name="Reduced Rate"
	 *
	 * @since 2.5.0
	 */
	public function create_class( $__, $assoc_args ) {
		try {
			$porcelain = isset( $assoc_args['porcelain'] );
			unset( $assoc_args['porcelain'] );

			$assoc_args = apply_filters( 'woocommerce_cli_create_tax_class_data', $assoc_args );

			// Check if name is specified.
			if ( ! isset( $assoc_args['name'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_missing_name', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'name' ) );
			}

			$name    = sanitize_text_field( $assoc_args['name'] );
			$slug    = sanitize_title( $name );
			$classes = WC_Tax::get_tax_classes();
			$exists  = false;

			// Check if class exists.
			foreach ( $classes as $key => $class ) {
				if ( sanitize_title( $class ) === $slug ) {
					$exists = true;
					break;
				}
			}

			// Return error if tax class already exists.
			if ( $exists ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_cannot_create_tax_class', __( 'Tax class already exists', 'woocommerce' ) );
			}

			// Add the new class
			$classes[] = $name;

			update_option( 'woocommerce_tax_classes', implode( "\n", $classes ) );

			do_action( 'woocommerce_cli_create_tax_class', $slug, array( 'name' => $name ) );

			if ( $porcelain ) {
				WP_CLI::line( $slug );
			} else {
				WP_CLI::success( "Created tax class $slug." );
			}
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Delete one or more tax rates.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The tax rate ID to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax delete 123
	 *
	 *     wp wc tax delete $(wp wc tax list --format=ids)
	 *
	 * @since 2.5.0
	 */
	public function delete( $args, $assoc_args ) {
		$exit_code = 0;

		foreach ( $args as $tax_id ) {
			$tax_id = absint( $tax_id );
			$tax    = WC_Tax::_get_tax_rate( $tax_id );

			if ( is_null( $tax ) ) {
				$exit_code += 1;
				WP_CLI::warning( "Failed deleting tax rate {$tax_id}." );
				continue;
			}

			do_action( 'woocommerce_cli_delete_tax_rate', $tax_id );

			WC_Tax::_delete_tax_rate( $tax_id );
			WP_CLI::success( "Deleted tax rate {$tax_id}." );
		}
		exit( $exit_code ? 1 : 0 );
	}

	/**
	 * Delete one or more tax classes.
	 *
	 * ## OPTIONS
	 *
	 * <slug>...
	 * : The tax class slug to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax delete_class reduced-rate
	 *
	 *     wp wc tax delete_class $(wp wc tax list_class --format=ids)
	 *
	 * @since 2.5.0
	 */
	public function delete_class( $args, $assoc_args ) {
		$classes   = WC_Tax::get_tax_classes();
		$exit_code = 0;

		foreach ( $args as $slug ) {
			$slug    = sanitize_title( $slug );
			$deleted = false;

			foreach ( $classes as $key => $class ) {
				if ( sanitize_title( $class ) === $slug ) {
					unset( $classes[ $key ] );
					$deleted = true;
					break;
				}
			}

			if ( $deleted ) {
				WP_CLI::success( "Deleted tax class {$slug}." );
			} else {
				$exit_code += 1;
				WP_CLI::warning( "Failed deleting tax class {$slug}." );
			}
		}

		update_option( 'woocommerce_tax_classes', implode( "\n", $classes ) );

		exit( $exit_code ? 1 : 0 );
	}

	/**
	 * Get a tax rate.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : Tax rate ID
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole tax rate fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the tax rates fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for get command:
	 *
	 * * id
	 * * country
	 * * state
	 * * postcode
	 * * city
	 * * rate
	 * * name
	 * * priority
	 * * compound
	 * * shipping
	 * * order
	 * * class
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax get 123 --field=rate
	 *
	 *     wp wc tax get 321 --format=json > rate321.json
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		global $wpdb;

		try {

			$tax_data = $this->format_taxes_to_items( array( $args[0] ) );

			if ( empty( $tax_data ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_tax_rate', sprintf( __( 'Invalid tax rate ID: %s', 'woocommerce' ), $args[0] ) );
			}

			$tax_data = apply_filters( 'woocommerce_cli_get_tax_rate', $tax_data[0] );

			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = array_keys( $tax_data );
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $tax_data );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * List taxes.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter tax based on tax property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each tax.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific tax fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each tax:
	 *
	 * * id
	 * * country
	 * * state
	 * * postcode
	 * * city
	 * * rate
	 * * name
	 * * priority
	 * * compound
	 * * shipping
	 * * class
	 *
	 * These fields are optionally available:
	 *
	 * * order
	 *
	 * Fields for filtering query result also available:
	 *
	 * * class Sort by tax class.
	 * * page  Page number.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax list
	 *
	 *     wp wc tax list --field=id
	 *
	 *     wp wc tax list --fields=id,rate,class --format=json
	 *
	 * @since      2.5.0
	 * @subcommand list
	 */
	public function list_( $__, $assoc_args ) {
		$query_args = $this->merge_tax_query_args( array(), $assoc_args );
		$formatter  = $this->get_formatter( $assoc_args );

		$taxes = $this->query_tax_rates( $query_args );

		if ( 'ids' === $formatter->format ) {
			$_taxes = array();
			foreach ( $taxes as $tax ) {
				$_taxes[] = $tax->tax_rate_id;
			}
			echo implode( ' ', $_taxes );
		} else {
			$items = $this->format_taxes_to_items( $taxes );
			$formatter->display_items( $items );
		}
	}

	/**
	 * List tax classes.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter tax class based on tax class property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each tax class.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific tax class fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each tax class:
	 *
	 * * slug
	 * * name
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax list_class
	 *
	 *     wp wc tax list_class --field=slug
	 *
	 *     wp wc tax list_class --format=json
	 *
	 * @since      2.5.0
	 * @subcommand list_class
	 */
	public function list_class( $__, $assoc_args ) {
		// Set default fields for tax classes
		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = 'slug,name';
		}

		$formatter = $this->get_formatter( $assoc_args );
		$items     = array();

		// Add standard class
		$items[] = array(
			'slug' => 'standard',
			'name' => __( 'Standard Rate', 'woocommerce' )
		);

		$classes = WC_Tax::get_tax_classes();

		foreach ( $classes as $class ) {
			$items[] = apply_filters( 'woocommerce_cli_tax_class_response', array(
				'slug' => sanitize_title( $class ),
				'name' => $class
			), $class, $assoc_args, $this );
		}

		if ( 'ids' === $formatter->format ) {
			$_slugs = array();
			foreach ( $items as $item ) {
				$_slugs[] = $item['slug'];
			}
			echo implode( ' ', $_slugs );
		} else {
			$formatter->display_items( $items );
		}
	}

	/**
	 * Update a tax rate.
	 *
	 * ## OPTIONS
	 *
	 * <id>
	 * : The ID of the tax rate to update.
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for update command:
	 *
	 * * country
	 * * state
	 * * postcode
	 * * city
	 * * rate
	 * * name
	 * * priority
	 * * compound
	 * * shipping
	 * * class
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc tax update 123 --rate=5
	 *
	 * @since 2.5.0
	 */
	public function update( $args, $assoc_args ) {
		try {
			// Get current tax rate data
			$tax_data = $this->format_taxes_to_items( array( $args[0] ) );

			if ( empty( $tax_data ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_tax_rate', sprintf( __( 'Invalid tax rate ID: %s', 'woocommerce' ), $args[0] ) );
			}

			$current_data   = $tax_data[0];
			$id             = $current_data['id'];
			$data           = apply_filters( 'woocommerce_cli_update_tax_rate_data', $assoc_args, $id );
			$new_data       = array();
			$default_fields = array(
				'tax_rate_country',
				'tax_rate_state',
				'tax_rate',
				'tax_rate_name',
				'tax_rate_priority',
				'tax_rate_compound',
				'tax_rate_shipping',
				'tax_rate_order',
				'tax_rate_class'
			);

			foreach ( $data as $key => $value ) {
				$new_key = 'rate' === $key ? 'tax_rate' : 'tax_rate_' . $key;

				// Check if the key is valid
				if ( ! in_array( $new_key, $default_fields ) ) {
					continue;
				}

				// Test new data against current data
				if ( $value === $current_data[ $key ] ) {
					continue;
				}

				// Fix compund and shipping values
				if ( in_array( $key, array( 'compound', 'shipping' ) ) ) {
					$value = $value ? 1 : 0;
				}

				$new_data[ $new_key ] = $value;
			}

			// Update tax rate
			WC_Tax::_update_tax_rate( $id, $new_data );

			// Update locales
			if ( ! empty( $data['postcode'] ) && $current_data['postcode'] != $data['postcode'] ) {
				WC_Tax::_update_tax_rate_postcodes( $id, wc_clean( $data['postcode'] ) );
			}

			if ( ! empty( $data['city'] ) && $current_data['city'] != $data['city'] ) {
				WC_Tax::_update_tax_rate_cities( $id, wc_clean( $data['city'] ) );
			}

			do_action( 'woocommerce_cli_update_tax_rate', $id, $data );

			WP_CLI::success( "Updated tax rate $id." );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Add common cli arguments to argument list before $wpdb->get_results() is run.
	 *
	 * @since  2.5.0
	 * @param  array $base_args  Required arguments for the query (e.g. `limit`, etc)
	 * @param  array $assoc_args Arguments provided in when invoking the command
	 * @return array
	 */
	protected function merge_tax_query_args( $base_args, $assoc_args ) {
		$args = array();

		if ( ! empty( $assoc_args['class'] ) ) {
			$args['class'] = $assoc_args['class'];
		}

		// Number of post to show per page.
		if ( ! empty( $assoc_args['limit'] ) ) {
			$args['posts_per_page'] = $assoc_args['limit'];
		}

		// posts page.
		$args['paged'] = ( isset( $assoc_args['page'] ) ) ? absint( $assoc_args['page'] ) : 1;

		$args = apply_filters( 'woocommerce_cli_tax_query_args', $args, $assoc_args );

		return array_merge( $base_args, $args );
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

		$query = "
			SELECT tax_rate_id
			FROM {$wpdb->prefix}woocommerce_tax_rates
			WHERE 1 = 1
		";

		// Filter by tax class
		if ( ! empty( $args['class'] ) ) {
			$class = 'standard' !== $args['class'] ? sanitize_title( $args['class'] ) : '';
			$query .= " AND tax_rate_class = '$class'";
		}

		// Order tax rates
		$order_by = ' ORDER BY tax_rate_order';

		// Pagination
		$per_page   = isset( $args['posts_per_page'] ) ? $args['posts_per_page'] : get_option( 'posts_per_page' );
		$offset     = 1 < $args['paged'] ? ( $args['paged'] - 1 ) * $per_page : 0;
		$pagination = sprintf( ' LIMIT %d, %d', $offset, $per_page );

		return $wpdb->get_results( $query . $order_by . $pagination );
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,country,state,postcode,city,rate,name,priority,compound,shipping,class';
	}

	/**
	 * Format taxes from query result to items in which each item contain
	 * common properties of item, for instance `tax_rate_id` will be `id`.
	 *
	 * @since  2.5.0
	 * @param  array $taxes Array of tax rate.
	 * @return array Items
	 */
	protected function format_taxes_to_items( $taxes ) {
		global $wpdb;

		$items = array();

		foreach ( $taxes as $tax_id ) {
			$id = is_object( $tax_id ) ? $tax_id->tax_rate_id : $tax_id;
			$id = absint( $id );

			// Get tax rate details
			$tax = WC_Tax::_get_tax_rate( $id );

			if ( is_wp_error( $tax ) || empty( $tax ) ) {
				continue;
			}

			$tax_data = array(
				'id'       => $tax['tax_rate_id'],
				'country'  => $tax['tax_rate_country'],
				'state'    => $tax['tax_rate_state'],
				'postcode' => '',
				'city'     => '',
				'rate'     => $tax['tax_rate'],
				'name'     => $tax['tax_rate_name'],
				'priority' => (int) $tax['tax_rate_priority'],
				'compound' => (bool) $tax['tax_rate_compound'],
				'shipping' => (bool) $tax['tax_rate_shipping'],
				'order'    => (int) $tax['tax_rate_order'],
				'class'    => $tax['tax_rate_class'] ? $tax['tax_rate_class'] : 'standard'
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

			$items[] = $tax_data;
		}

		return $items;
	}
}
