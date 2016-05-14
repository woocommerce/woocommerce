<?php

/**
 * Manage Coupons.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Coupon extends WC_CLI_Command {

	/**
	 * Create a coupon.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new coupon.
	 *
	 * [--porcelain]
	 * : Outputs just the new coupon id.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for create command:
	 *
	 * * code
	 * * type
	 * * amount
	 * * description
	 * * expiry_date
	 * * individual_use
	 * * product_ids
	 * * exclude_product_ids
	 * * usage_limit
	 * * usage_limit_per_user
	 * * limit_usage_to_x_items
	 * * usage_count
	 * * enable_free_shipping
	 * * product_category_ids
	 * * exclude_product_category_ids
	 * * minimum_amount
	 * * maximum_amount
	 * * customer_emails
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc coupon create --code=new-coupon --type=percent
	 *
	 */
	public function create( $__, $assoc_args ) {
		global $wpdb;

		try {
			$porcelain = isset( $assoc_args['porcelain'] );
			unset( $assoc_args['porcelain'] );

			$assoc_args = apply_filters( 'woocommerce_cli_create_coupon_data', $assoc_args );

			// Check if coupon code is specified.
			if ( ! isset( $assoc_args['code'] ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_missing_coupon_code', sprintf( __( 'Missing parameter %s', 'woocommerce' ), 'code' ) );
			}

			$coupon_code = apply_filters( 'woocommerce_coupon_code', $assoc_args['code'] );

			// Check for duplicate coupon codes.
			$coupon_found = $wpdb->get_var( $wpdb->prepare( "
				SELECT $wpdb->posts.ID
				FROM $wpdb->posts
				WHERE $wpdb->posts.post_type = 'shop_coupon'
				AND $wpdb->posts.post_status = 'publish'
				AND $wpdb->posts.post_title = '%s'
			 ", $coupon_code ) );

			if ( $coupon_found ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce' ) );
			}

			$defaults = array(
				'type'                         => 'fixed_cart',
				'amount'                       => 0,
				'individual_use'               => false,
				'product_ids'                  => array(),
				'exclude_product_ids'          => array(),
				'usage_limit'                  => '',
				'usage_limit_per_user'         => '',
				'limit_usage_to_x_items'       => '',
				'usage_count'                  => '',
				'expiry_date'                  => '',
				'enable_free_shipping'         => false,
				'product_category_ids'         => array(),
				'exclude_product_category_ids' => array(),
				'exclude_sale_items'           => false,
				'minimum_amount'               => '',
				'maximum_amount'               => '',
				'customer_emails'              => array(),
				'description'                  => ''
			);

			$coupon_data = wp_parse_args( $assoc_args, $defaults );

			// Validate coupon types
			if ( ! in_array( wc_clean( $coupon_data['type'] ), array_keys( wc_get_coupon_types() ) ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_coupon_type', sprintf( __( 'Invalid coupon type - the coupon type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_coupon_types() ) ) ) );
			}

			$new_coupon = array(
				'post_title'   => $coupon_code,
				'post_content' => '',
				'post_status'  => 'publish',
				'post_author'  => get_current_user_id(),
				'post_type'    => 'shop_coupon',
				'post_excerpt' => $coupon_data['description']
	 		);

			$id = wp_insert_post( $new_coupon, $wp_error = false );

			if ( is_wp_error( $id ) ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_cannot_create_coupon', $id->get_error_message() );
			}

			// Set coupon meta
			update_post_meta( $id, 'discount_type', $coupon_data['type'] );
			update_post_meta( $id, 'coupon_amount', wc_format_decimal( $coupon_data['amount'] ) );
			update_post_meta( $id, 'individual_use', ( $this->is_true( $coupon_data['individual_use'] ) ) ? 'yes' : 'no' );
			update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['product_ids'] ) ) ) );
			update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $coupon_data['exclude_product_ids'] ) ) ) );
			update_post_meta( $id, 'usage_limit', absint( $coupon_data['usage_limit'] ) );
			update_post_meta( $id, 'usage_limit_per_user', absint( $coupon_data['usage_limit_per_user'] ) );
			update_post_meta( $id, 'limit_usage_to_x_items', absint( $coupon_data['limit_usage_to_x_items'] ) );
			update_post_meta( $id, 'usage_count', absint( $coupon_data['usage_count'] ) );
			update_post_meta( $id, 'expiry_date', $this->get_coupon_expiry_date( wc_clean( $coupon_data['expiry_date'] ) ) );
			update_post_meta( $id, 'free_shipping', ( $this->is_true( $coupon_data['enable_free_shipping'] ) ) ? 'yes' : 'no' );
			update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $coupon_data['product_category_ids'] ) ) );
			update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $coupon_data['exclude_product_category_ids'] ) ) );
			update_post_meta( $id, 'exclude_sale_items', ( $this->is_true( $coupon_data['exclude_sale_items'] ) ) ? 'yes' : 'no' );
			update_post_meta( $id, 'minimum_amount', wc_format_decimal( $coupon_data['minimum_amount'] ) );
			update_post_meta( $id, 'maximum_amount', wc_format_decimal( $coupon_data['maximum_amount'] ) );
			update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $coupon_data['customer_emails'] ) ) );

			do_action( 'woocommerce_cli_create_coupon', $id, $coupon_data );

			if ( $porcelain ) {
				WP_CLI::line( $id );
			} else {
				WP_CLI::success( "Created coupon $id." );
			}
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Delete one or more coupons.
	 *
	 * ## OPTIONS
	 *
	 * <id>...
	 * : The coupon ID to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc coupon delete 123
	 *
	 *     wp wc coupon delete $(wp wc coupon list --format=ids)
	 *
	 */
	public function delete( $args, $assoc_args ) {
		$exit_code = 0;
		foreach ( $this->get_many_coupons_from_ids_or_codes( $args, true ) as $coupon ) {
			do_action( 'woocommerce_cli_delete_coupon', $coupon->id );
			$r = wp_delete_post( $coupon->id, true );

			if ( $r ) {
				WP_CLI::success( "Deleted coupon {$coupon->id}." );
			} else {
				$exit_code += 1;
				WP_CLI::warning( "Failed deleting coupon {$coupon->id}." );
			}
		}
		exit( $exit_code ? 1 : 0 );
	}

	/**
	 * Get a coupon.
	 *
	 * ## OPTIONS
	 *
	 * <coupon>
	 * : Coupon ID or code
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole coupon fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the coupon's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for get command:
	 *
	 * * id
	 * * code
	 * * type
	 * * amount
	 * * description
	 * * expiry_date
	 * * individual_use
	 * * product_ids
	 * * exclude_product_ids
	 * * usage_limit
	 * * usage_limit_per_user
	 * * limit_usage_to_x_items
	 * * usage_count
	 * * enable_free_shipping
	 * * product_category_ids
	 * * exclude_product_category_ids
	 * * minimum_amount
	 * * maximum_amount
	 * * customer_emails
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc coupon get 123 --field=discount_type
	 *
	 *     wp wc coupon get disc50 --format=json > disc50.json
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		global $wpdb;

		try {
			$coupon = $this->get_coupon_from_id_or_code( $args[0] );
			if ( ! $coupon ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_coupon', sprintf( __( 'Invalid coupon ID or code: %s', 'woocommerce' ), $args[0] ) );
			}

			$coupon_post = get_post( $coupon->id );
			$coupon_data = array(
				'id'                           => $coupon->id,
				'code'                         => $coupon->code,
				'type'                         => $coupon->type,
				'created_at'                   => $this->format_datetime( $coupon_post->post_date_gmt ),
				'updated_at'                   => $this->format_datetime( $coupon_post->post_modified_gmt ),
				'amount'                       => wc_format_decimal( $coupon->coupon_amount, 2 ),
				'individual_use'               => $coupon->individual_use,
				'product_ids'                  => $coupon_post->product_ids,
				'exclude_product_ids'          => $coupon_post->exclude_product_ids,
				'usage_limit'                  => ( ! empty( $coupon->usage_limit ) ) ? $coupon->usage_limit : null,
				'usage_limit_per_user'         => ( ! empty( $coupon->usage_limit_per_user ) ) ? $coupon->usage_limit_per_user : null,
				'limit_usage_to_x_items'       => (int) $coupon->limit_usage_to_x_items,
				'usage_count'                  => (int) $coupon->usage_count,
				'expiry_date'                  => ( ! empty( $coupon->expiry_date ) ) ? $this->format_datetime( $coupon->expiry_date ) : null,
				'enable_free_shipping'         => $coupon->free_shipping,
				'product_category_ids'         => implode( ', ', $coupon->product_categories ),
				'exclude_product_category_ids' => implode( ', ', $coupon->exclude_product_categories ),
				'exclude_sale_items'           => $coupon->exclude_sale_items,
				'minimum_amount'               => wc_format_decimal( $coupon->minimum_amount, 2 ),
				'maximum_amount'               => wc_format_decimal( $coupon->maximum_amount, 2 ),
				'customer_emails'              => implode( ', ', $coupon->customer_email ),
				'description'                  => $coupon_post->post_excerpt,
			);

			$coupon_data = apply_filters( 'woocommerce_cli_get_coupon', $coupon_data );

			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = array_keys( $coupon_data );
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $coupon_data );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * List coupons.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter coupon based on coupon property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each coupon.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific coupon fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each coupon:
	 *
	 * * id
	 * * code
	 * * type
	 * * amount
	 * * description
	 * * expiry_date
	 *
	 * These fields are optionally available:
	 *
	 * * individual_use
	 * * product_ids
	 * * exclude_product_ids
	 * * usage_limit
	 * * usage_limit_per_user
	 * * limit_usage_to_x_items
	 * * usage_count
	 * * free_shipping
	 * * product_category_ids
	 * * exclude_product_category_ids
	 * * exclude_sale_items
	 * * minimum_amount
	 * * maximum_amount
	 * * customer_emails
	 *
	 * Fields for filtering query result also available:
	 *
	 * * q              Filter coupons with search query.
	 * * in             Specify coupon IDs to retrieve.
	 * * not_in         Specify coupon IDs NOT to retrieve.
	 * * created_at_min Filter coupons created after this date.
	 * * created_at_max Filter coupons created before this date.
	 * * updated_at_min Filter coupons updated after this date.
	 * * updated_at_max Filter coupons updated before this date.
	 * * page           Page number.
	 * * offset         Number of coupon to displace or pass over.
	 * * order          Accepted values: ASC and DESC. Default: DESC.
	 * * orderby        Sort retrieved coupons by parameter. One or more options can be passed.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc coupon list
	 *
	 *     wp wc coupon list --field=id
	 *
	 *     wp wc coupon list --fields=id,code,type --format=json
	 *
	 * @since      2.5.0
	 * @subcommand list
	 */
	public function list_( $__, $assoc_args ) {
		$query_args = $this->merge_wp_query_args( $this->get_list_query_args(), $assoc_args );
		$formatter  = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query = new WP_Query( $query_args );
			echo implode( ' ', $query->posts );
		} else {
			$query = new WP_Query( $query_args );
			$items = $this->format_posts_to_items( $query->posts );
			$formatter->display_items( $items );
		}
	}

	/**
	 * Get coupon types.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc coupon types
	 *
	 * @since 2.5.0
	 */
	public function types( $__, $___ ) {
		$coupon_types = wc_get_coupon_types();
		foreach ( $coupon_types as $type => $label ) {
			WP_CLI::line( sprintf( '%s: %s', $label, $type ) );
		}
	}

	/**
	 * Update one or more coupons.
	 *
	 * ## OPTIONS
	 *
	 * <coupon>
	 * : The ID or code of the coupon to update.
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for update command:
	 *
	 * * code
	 * * type
	 * * amount
	 * * description
	 * * expiry_date
	 * * individual_use
	 * * product_ids
	 * * exclude_product_ids
	 * * usage_limit
	 * * usage_limit_per_user
	 * * limit_usage_to_x_items
	 * * usage_count
	 * * enable_free_shipping
	 * * product_category_ids
	 * * exclude_product_categories
	 * * exclude_product_category_ids
	 * * minimum_amount
	 * * maximum_amount
	 * * customer_emails
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc coupon update 123 --amount=5
	 *
	 *     wp wc coupon update coupon-code --code=new-coupon-code
	 *
	 * @since 2.5.0
	 */
	public function update( $args, $assoc_args ) {
		try {
			$coupon = $this->get_coupon_from_id_or_code( $args[0] );
			if ( ! $coupon ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_invalid_coupon', sprintf( __( 'Invalid coupon ID or code: %s', 'woocommerce' ), $args[0] ) );
			}

			$id          = $coupon->id;
			$coupon_code = $coupon->code;
			$data        = apply_filters( 'woocommerce_cli_update_coupon_data', $assoc_args, $id );
			if ( isset( $data['code'] ) ) {
				global $wpdb;

				$coupon_code = apply_filters( 'woocommerce_coupon_code', $data['code'] );

				// Check for duplicate coupon codes
				$coupon_found = $wpdb->get_var( $wpdb->prepare( "
					SELECT $wpdb->posts.ID
					FROM $wpdb->posts
					WHERE $wpdb->posts.post_type = 'shop_coupon'
					AND $wpdb->posts.post_status = 'publish'
					AND $wpdb->posts.post_title = '%s'
					AND $wpdb->posts.ID != %s
				 ", $coupon_code, $id ) );

				if ( $coupon_found ) {
					throw new WC_CLI_Exception( 'woocommerce_cli_coupon_code_already_exists', __( 'The coupon code already exists', 'woocommerce' ) );
				}
			}

			$id = wp_update_post( array( 'ID' => intval( $id ), 'post_title' => $coupon_code, 'post_excerpt' => isset( $data['description'] ) ? $data['description'] : '' ) );
			if ( 0 === $id ) {
				throw new WC_CLI_Exception( 'woocommerce_cli_cannot_update_coupon', __( 'Failed to update coupon', 'woocommerce' ) );
			}

			if ( isset( $data['type'] ) ) {
				// Validate coupon types.
				if ( ! in_array( wc_clean( $data['type'] ), array_keys( wc_get_coupon_types() ) ) ) {
					throw new WC_CLI_Exception( 'woocommerce_cli_invalid_coupon_type', sprintf( __( 'Invalid coupon type - the coupon type must be any of these: %s', 'woocommerce' ), implode( ', ', array_keys( wc_get_coupon_types() ) ) ) );
				}
				update_post_meta( $id, 'discount_type', $data['type'] );
			}

			if ( isset( $data['amount'] ) ) {
				update_post_meta( $id, 'coupon_amount', wc_format_decimal( $data['amount'] ) );
			}

			if ( isset( $data['individual_use'] ) ) {
				update_post_meta( $id, 'individual_use', ( $this->is_true( $data['individual_use'] ) ) ? 'yes' : 'no' );
			}

			if ( isset( $data['product_ids'] ) ) {
				update_post_meta( $id, 'product_ids', implode( ',', array_filter( array_map( 'intval', $data['product_ids'] ) ) ) );
			}

			if ( isset( $data['exclude_product_ids'] ) ) {
				update_post_meta( $id, 'exclude_product_ids', implode( ',', array_filter( array_map( 'intval', $data['exclude_product_ids'] ) ) ) );
			}

			if ( isset( $data['usage_limit'] ) ) {
				update_post_meta( $id, 'usage_limit', absint( $data['usage_limit'] ) );
			}

			if ( isset( $data['usage_limit_per_user'] ) ) {
				update_post_meta( $id, 'usage_limit_per_user', absint( $data['usage_limit_per_user'] ) );
			}

			if ( isset( $data['limit_usage_to_x_items'] ) ) {
				update_post_meta( $id, 'limit_usage_to_x_items', absint( $data['limit_usage_to_x_items'] ) );
			}

			if ( isset( $data['usage_count'] ) ) {
				update_post_meta( $id, 'usage_count', absint( $data['usage_count'] ) );
			}

			if ( isset( $data['expiry_date'] ) ) {
				update_post_meta( $id, 'expiry_date', $this->get_coupon_expiry_date( wc_clean( $data['expiry_date'] ) ) );
			}

			if ( isset( $data['enable_free_shipping'] ) ) {
				update_post_meta( $id, 'free_shipping', ( $this->is_true( $data['enable_free_shipping'] ) ) ? 'yes' : 'no' );
			}

			if ( isset( $data['product_category_ids'] ) ) {
				update_post_meta( $id, 'product_categories', array_filter( array_map( 'intval', $data['product_category_ids'] ) ) );
			}

			if ( isset( $data['exclude_product_category_ids'] ) ) {
				update_post_meta( $id, 'exclude_product_categories', array_filter( array_map( 'intval', $data['exclude_product_category_ids'] ) ) );
			}

			if ( isset( $data['exclude_sale_items'] ) ) {
				update_post_meta( $id, 'exclude_sale_items', ( $this->is_true( $data['exclude_sale_items'] ) ) ? 'yes' : 'no' );
			}

			if ( isset( $data['minimum_amount'] ) ) {
				update_post_meta( $id, 'minimum_amount', wc_format_decimal( $data['minimum_amount'] ) );
			}

			if ( isset( $data['maximum_amount'] ) ) {
				update_post_meta( $id, 'maximum_amount', wc_format_decimal( $data['maximum_amount'] ) );
			}

			if ( isset( $data['customer_emails'] ) ) {
				update_post_meta( $id, 'customer_email', array_filter( array_map( 'sanitize_email', $data['customer_emails'] ) ) );
			}

			do_action( 'woocommerce_cli_update_coupon', $id, $data );

			WP_CLI::success( "Updated coupon $id." );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get query args for list subcommand.
	 *
	 * @since  2.5.0
	 * @return array
	 */
	protected function get_list_query_args() {
		return array(
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'order'          => 'DESC',
		);
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,code,type,amount,description,expiry_date';
	}

	/**
	 * Format posts from WP_Query result to items in which each item contain
	 * common properties of item, for instance `post_title` will be `code`.
	 *
	 * @since  2.5.0
	 * @param  array $posts Array of post
	 * @return array Items
	 */
	protected function format_posts_to_items( $posts ) {
		$items = array();
		foreach ( $posts as $post ) {
			$items[] = array(
				'id'                           => $post->ID,
				'code'                         => $post->post_title,
				'type'                         => $post->discount_type,
				'created_at'                   => $this->format_datetime( $post->post_date_gmt ),
				'updated_at'                   => $this->format_datetime( $post->post_modified_gmt ),
				'amount'                       => wc_format_decimal( $post->coupon_amount, 2 ),
				'individual_use'               => $post->individual_use,
				'product_ids'                  => $post->product_ids,
				'exclude_product_ids'          => $post->exclude_product_ids,
				'usage_limit'                  => ( ! empty( $post->usage_limit ) ) ? $post->usage_limit : null,
				'usage_limit_per_user'         => ( ! empty( $post->usage_limit_per_user ) ) ? $post->usage_limit_per_user : null,
				'limit_usage_to_x_items'       => (int) $post->limit_usage_to_x_items,
				'usage_count'                  => (int) $post->usage_count,
				'expiry_date'                  => ( ! empty( $post->expiry_date ) ) ? $this->format_datetime( $post->expiry_date ) : null,
				'free_shipping'                => $post->free_shipping,
				'product_category_ids'         => implode( ', ', is_array( $post->product_categories ) ? $post->product_categories : array() ),
				'exclude_product_category_ids' => implode( ', ', is_array( $post->exclude_product_categories ) ? $post->exclude_product_categories : array() ),
				'exclude_sale_items'           => $post->exclude_sale_items,
				'minimum_amount'               => wc_format_decimal( $post->minimum_amount, 2 ),
				'maximum_amount'               => wc_format_decimal( $post->maximum_amount, 2 ),
				'customer_emails'              => implode( ', ', is_array( $post->customer_email ) ? $post->customer_email : array() ),
				'description'                  => $post->post_excerpt,
			);
		}

		return $items;
	}

	/**
	 * Get expiry_date format before saved into DB.
	 *
	 * @since  2.5.0
	 * @param  string $expiry_date
	 * @return string
	 */
	protected function get_coupon_expiry_date( $expiry_date ) {
		if ( '' !== $expiry_date ) {
			return date( 'Y-m-d', strtotime( $expiry_date ) );
		}

		return '';
	}

	/**
	 * Get coupon from coupon's ID or code.
	 *
	 * @since  2.5.0
	 * @param  int|string $coupon_id_or_code Coupon's ID or code
	 * @param  bool       $display_warning   Display warning if ID or code is invalid. Default false.
	 * @return WC_Coupon
	 */
	protected function get_coupon_from_id_or_code( $coupon_id_or_code, $display_warning = false ) {
		global $wpdb;

		$code = $wpdb->get_var( $wpdb->prepare( "SELECT post_title FROM $wpdb->posts WHERE (id = %s OR post_title = %s) AND post_type = 'shop_coupon' AND post_status = 'publish' LIMIT 1", $coupon_id_or_code, $coupon_id_or_code ) );
		if ( ! $code ) {
			if ( $display_warning ) {
				WP_CLI::warning( "Invalid coupon ID or code $coupon_id_or_code" );
			}
			return null;
		}

		return new WC_Coupon( $code );
	}

	/**
	 * Get coupon from coupon's ID or code.
	 *
	 * @since  2.5.0
	 * @param  array     $args            Coupon's IDs or codes
	 * @param  bool      $display_warning Display warning if ID or code is invalid. Default false.
	 * @return WC_Coupon
	 */
	protected function get_many_coupons_from_ids_or_codes( $args, $display_warning = false ) {
		$coupons = array();

		foreach ( $args as $arg ) {
			$code = $this->get_coupon_from_id_or_code( $arg, $display_warning );
			if ( $code ) {
				$coupons[] = $code;
			}
		}

		return $coupons;
	}
}
