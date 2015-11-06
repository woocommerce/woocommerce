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
	 *     wp wc tax list_classes
	 *
	 *     wp wc tax list_classes --field=slug
	 *
	 *     wp wc tax list_classes --format=json
	 *
	 * @since      2.5.0
	 * @subcommand list_classes
	 */
	public function list_classes( $__, $assoc_args ) {
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
			$id = $tax_id->tax_rate_id;

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
