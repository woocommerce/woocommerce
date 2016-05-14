<?php

/**
 * Manage Customers.
 *
 * @since    2.5.0
 * @package  WooCommerce/CLI
 * @category CLI
 * @author   WooThemes
 */
class WC_CLI_Customer extends WC_CLI_Command {

	/**
	 * Create a customer.
	 *
	 * ## OPTIONS
	 *
	 * <email>
	 * : The email address of the customer to create.
	 *
	 * [--<field>=<value>]
	 * : Associative args for the new customer.
	 *
	 * [--porcelain]
	 * : Outputs just the new customer id.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are optionally available for create command:
	 *
	 * * username
	 * * password
	 * * first_name
	 * * last_name
	 *
	 * Billing address fields:
	 *
	 * * billing_address.first_name
	 * * billing_address.last_name
	 * * billing_address.company
	 * * billing_address.address_1
	 * * billing_address.address_2
	 * * billing_address.city
	 * * billing_address.state
	 * * billing_address.postcode
	 * * billing_address.country
	 * * billing_address.email
	 * * billing_address.phone
	 *
	 * Shipping address fields:
	 *
	 * * shipping_address.first_name
	 * * shipping_address.last_name
	 * * shipping_address.company
	 * * shipping_address.address_1
	 * * shipping_address.address_2
	 * * shipping_address.city
	 * * shipping_address.state
	 * * shipping_address.postcode
	 * * shipping_address.country
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer create new-customer@example.com --first_name=Akeda
	 *
	 * @since 2.5.0
	 */
	public function create( $args, $assoc_args ) {
		global $wpdb;

		try {
			$porcelain = isset( $assoc_args['porcelain'] );
			unset( $assoc_args['porcelain'] );

			$assoc_args['email'] = $args[0];
			$data                = apply_filters( 'woocommerce_cli_create_customer_data', $this->unflatten_array( $assoc_args ) );

			// Sets the username.
			$data['username'] = ! empty( $data['username'] ) ? $data['username'] : '';

			// Sets the password.
			$data['password'] = ! empty( $data['password'] ) ? $data['password'] : '';

			// Attempts to create the new customer.
			$id = wc_create_new_customer( $data['email'], $data['username'], $data['password'] );

			// Checks for an error in the customer creation.
			if ( is_wp_error( $id ) ) {
				throw new WC_CLI_Exception( $id->get_error_code(), $id->get_error_message() );
			}

			// Added customer data.
			$this->update_customer_data( $id, $data );

			do_action( 'woocommerce_cli_create_customer', $id, $data );

			if ( $porcelain ) {
				WP_CLI::line( $id );
			} else {
				WP_CLI::success( "Created customer $id." );
			}
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Delete one or more customers.
	 *
	 * ## OPTIONS
	 *
	 * <customer>...
	 * : The customer ID, email, or username to delete.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer delete 123
	 *
	 *     wp wc customer delete $(wp wc customer list --format=ids)
	 *
	 * @since 2.5.0
	 */
	public function delete( $args, $assoc_args ) {
		$exit_code = 0;
		foreach ( $args as $arg ) {
			try {
				$customer = $this->get_user( $arg );
				do_action( 'woocommerce_cli_delete_customer', $customer['id'] );
				$r = wp_delete_user( $customer['id'] );

				if ( $r ) {
					WP_CLI::success( "Deleted customer {$customer['id']}." );
				} else {
					$exit_code += 1;
					WP_CLI::warning( "Failed deleting customer {$customer['id']}." );
				}
			} catch ( WC_CLI_Exception $e ) {
				WP_CLI::warning( $e->getMessage() );
			}
		}
		exit( $exit_code ? 1 : 0 );
	}

	/**
	 * View customer downloads.
	 *
	 * ## OPTIONS
	 *
	 * <customer>
	 * : The customer ID, email or username.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole customer fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the customer's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * download_id
	 * * download_name
	 * * access_expires
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer downloads 123
	 *
	 * @since 2.5.0
	 */
	public function downloads( $args, $assoc_args ) {
		try {
			$user      = $this->get_user( $args[0] );
			$downloads = array();
			foreach ( wc_get_customer_available_downloads( $user['id'] ) as $key => $download ) {
				$downloads[ $key ] = $download;
				$downloads[ $key ]['access_expires'] = $this->format_datetime( $download['access_expires'] );
			}
			$downloads = apply_filters( 'woocommerce_cli_customer_downloads', $downloads, $user, $assoc_args );

			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = $this->get_customer_download_fields();
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_items( $downloads );

		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Get a customer.
	 *
	 * ## OPTIONS
	 *
	 * <customer>
	 * : Customer ID, email, or username.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole customer fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the customer's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * * id
	 * * email
	 * * first_name
	 * * last_name
	 * * created_at
	 * * username
	 * * last_order_id
	 * * last_order_date
	 * * orders_count
	 * * total_spent
	 * * avatar_url
	 *
	 * Billing address fields:
	 *
	 * * billing_address.first_name
	 * * billing_address.last_name
	 * * billing_address.company
	 * * billing_address.address_1
	 * * billing_address.address_2
	 * * billing_address.city
	 * * billing_address.state
	 * * billing_address.postcode
	 * * billing_address.country
	 * * billing_address.email
	 * * billing_address.phone
	 *
	 * Shipping address fields:
	 *
	 * * shipping_address.first_name
	 * * shipping_address.last_name
	 * * shipping_address.company
	 * * shipping_address.address_1
	 * * shipping_address.address_2
	 * * shipping_address.city
	 * * shipping_address.state
	 * * shipping_address.postcode
	 * * shipping_address.country
	 *
	 * Fields for filtering query result also available:
	 *
	 * * role           Filter customers associated with certain role.
	 * * q              Filter customers with search query.
	 * * created_at_min Filter customers whose registered after this date.
	 * * created_at_max Filter customers whose registered before this date.
	 * * limit          The maximum returned number of results.
	 * * offset         Offset the returned results.
	 * * order          Accepted values: ASC and DESC. Default: DESC.
	 * * orderby        Sort retrieved customers by parameter. One or more options can be passed.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer get 123 --field=email
	 *
	 *     wp wc customer get customer-login --format=json
	 *
	 * @since 2.5.0
	 */
	public function get( $args, $assoc_args ) {
		try {
			$user = $this->get_user( $args[0] );

			if ( empty( $assoc_args['fields'] ) ) {
				$assoc_args['fields'] = array_keys( $user );
			}

			$formatter = $this->get_formatter( $assoc_args );
			$formatter->display_item( $user );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * List customers.
	 *
	 * ## OPTIONS
	 *
	 * [--<field>=<value>]
	 * : Filter customer based on customer property.
	 *
	 * [--field=<field>]
	 * : Prints the value of a single field for each customer.
	 *
	 * [--fields=<fields>]
	 * : Limit the output to specific customer fields.
	 *
	 * [--format=<format>]
	 * : Acceptec values: table, csv, json, count, ids. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields will be displayed by default for each customer:
	 *
	 * * id
	 * * email
	 * * first_name
	 * * last_name
	 * * created_at
	 *
	 * These fields are optionally available:
	 *
	 * * username
	 * * last_order_id
	 * * last_order_date
	 * * orders_count
	 * * total_spent
	 * * avatar_url
	 *
	 * Billing address fields:
	 *
	 * * billing_address.first_name
	 * * billing_address.last_name
	 * * billing_address.company
	 * * billing_address.address_1
	 * * billing_address.address_2
	 * * billing_address.city
	 * * billing_address.state
	 * * billing_address.postcode
	 * * billing_address.country
	 * * billing_address.email
	 * * billing_address.phone
	 *
	 * Shipping address fields:
	 *
	 * * shipping_address.first_name
	 * * shipping_address.last_name
	 * * shipping_address.company
	 * * shipping_address.address_1
	 * * shipping_address.address_2
	 * * shipping_address.city
	 * * shipping_address.state
	 * * shipping_address.postcode
	 * * shipping_address.country
	 *
	 * Fields for filtering query result also available:
	 *
	 * * role           Filter customers associated with certain role.
	 * * q              Filter customers with search query.
	 * * created_at_min Filter customers whose registered after this date.
	 * * created_at_max Filter customers whose registered before this date.
	 * * limit          The maximum returned number of results.
	 * * offset         Offset the returned results.
	 * * order          Accepted values: ASC and DESC. Default: DESC.
	 * * orderby        Sort retrieved customers by parameter. One or more options can be passed.
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer list
	 *
	 *     wp wc customer list --field=id
	 *
	 *     wp wc customer list --fields=id,email,first_name --format=json
	 *
	 * @subcommand list
	 * @since      2.5.0
	 */
	public function list_( $__, $assoc_args ) {
		$query_args = $this->merge_wp_user_query_args( $this->get_list_query_args(), $assoc_args );
		$formatter  = $this->get_formatter( $assoc_args );

		if ( 'ids' === $formatter->format ) {
			$query_args['fields'] = 'ids';
			$query = new WP_User_Query( $query_args );
			echo implode( ' ', $query->results );
		} else {
			$query = new WP_User_Query( $query_args );
			$items = $this->format_users_to_items( $query->results );
			$formatter->display_items( $items );
		}
	}

	/**
	 * View customer orders.
	 *
	 * ## OPTIONS
	 *
	 * <customer>
	 * : The customer ID, email or username.
	 *
	 * [--field=<field>]
	 * : Instead of returning the whole customer fields, returns the value of a single fields.
	 *
	 * [--fields=<fields>]
	 * : Get a specific subset of the customer's fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * For more fields, see: wp wc order list --help
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer orders 123
	 *
	 * @since 2.5.0
	 */
	public function orders( $args, $assoc_args ) {
		try {
			WP_CLI::run_command( array( 'wc', 'order', 'list' ), array( 'customer_id' => $args[0] ) );
		} catch ( WC_CLI_Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}
	}

	/**
	 * Update one or more customers.
	 *
	 * ## OPTIONS
	 *
	 * <customer>
	 * : Customer ID, email, or username.
	 *
	 * [--<field>=<value>]
	 * : One or more fields to update.
	 *
	 * ## AVAILABLE FIELDS
	 *
	 * These fields are available for update command:
	 *
	 * * email
	 * * password
	 * * first_name
	 * * last_name
	 *
	 * Billing address fields:
	 *
	 * * billing_address.first_name
	 * * billing_address.last_name
	 * * billing_address.company
	 * * billing_address.address_1
	 * * billing_address.address_2
	 * * billing_address.city
	 * * billing_address.state
	 * * billing_address.postcode
	 * * billing_address.country
	 * * billing_address.email
	 * * billing_address.phone
	 *
	 * Shipping address fields:
	 *
	 * * shipping_address.first_name
	 * * shipping_address.last_name
	 * * shipping_address.company
	 * * shipping_address.address_1
	 * * shipping_address.address_2
	 * * shipping_address.city
	 * * shipping_address.state
	 * * shipping_address.postcode
	 * * shipping_address.country
	 *
	 * ## EXAMPLES
	 *
	 *     wp wc customer update customer-login --first_name=akeda --last_name=bagus
	 *
	 *     wp wc customer update customer@example.com --password=new-password
	 *
	 * @since 2.5.0
	 */
	public function update( $args, $assoc_args ) {
		try {
			$user = $this->get_user( $args[0] );
			$data = $this->unflatten_array( $assoc_args );
			$data = apply_filters( 'woocommerce_cli_update_customer_data', $data );

			// Customer email.
			if ( isset( $data['email'] ) ) {
				wp_update_user( array( 'ID' => $user['id'], 'user_email' => sanitize_email( $data['email'] ) ) );
			}

			// Customer password.
			if ( isset( $data['password'] ) ) {
				wp_update_user( array( 'ID' => $user['id'], 'user_pass' => wc_clean( $data['password'] ) ) );
			}

			// Update customer data.
			$this->update_customer_data( $user['id'], $data );

			do_action( 'woocommerce_cli_update_customer', $user['id'], $data );

			WP_CLI::success( "Updated customer {$user['id']}." );
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
			'role'    => 'customer',
			'orderby' => 'registered',
		);
	}

	/**
	 * Get default format fields that will be used in `list` and `get` subcommands.
	 *
	 * @since  2.5.0
	 * @return string
	 */
	protected function get_default_format_fields() {
		return 'id,email,first_name,last_name,created_at';
	}

	/**
	 * Format users from WP_User_Query result to items in which each item contain
	 * common properties of item.
	 *
	 * @since  2.5.0
	 * @param  array $users Array of user
	 * @return array Items
	 */
	protected function format_users_to_items( $users ) {
		$items = array();
		foreach ( $users as $user ) {
			try {
				$items[] = $this->get_user( $user->ID );
			} catch ( WC_CLI_Exception $e ) {
				WP_CLI::warning( $e->getMessage() );
			}
		}

		return $items;
	}

	/**
	 * Get user from given user ID, email, or login
	 *
	 * @throws WC_CLI_Exception
	 *
	 * @since  2.5.0
	 * @param  mixed          $id_email_or_login
	 * @return array|WP_Error
	 */
	protected function get_user( $id_email_or_login ) {
		global $wpdb;

		if ( is_numeric( $id_email_or_login ) ) {
			$user = get_user_by( 'id', $id_email_or_login );
		} else if ( is_email( $id_email_or_login ) ) {
			$user = get_user_by( 'email', $id_email_or_login );
		} else {
			$user = get_user_by( 'login', $id_email_or_login );
		}

		if ( ! $user ) {
			throw new WC_CLI_Exception( 'woocommerce_cli_invalid_customer', sprintf( __( 'Invalid customer "%s"', 'woocommerce' ), $id_email_or_login ) );
		}

		// Get info about user's last order
		$last_order = $wpdb->get_row( "SELECT id, post_date_gmt
						FROM $wpdb->posts AS posts
						LEFT JOIN {$wpdb->postmeta} AS meta on posts.ID = meta.post_id
						WHERE meta.meta_key = '_customer_user'
						AND   meta.meta_value = {$user->ID}
						AND   posts.post_type = 'shop_order'
						AND   posts.post_status IN ( '" . implode( "','", array_keys( wc_get_order_statuses() ) ) . "' )
						ORDER BY posts.ID DESC
					" );

		$customer = array(
			'id'               => $user->ID,
			'created_at'       => $this->format_datetime( $user->user_registered ),
			'email'            => $user->user_email,
			'first_name'       => $user->first_name,
			'last_name'        => $user->last_name,
			'username'         => $user->user_login,
			'role'             => $user->roles[0],
			'last_order_id'    => is_object( $last_order ) ? $last_order->id : null,
			'last_order_date'  => is_object( $last_order ) ? $this->format_datetime( $last_order->post_date_gmt ) : null,
			'orders_count'     => wc_get_customer_order_count( $user->ID ),
			'total_spent'      => wc_format_decimal( wc_get_customer_total_spent( $user->ID ), 2 ),
			'avatar_url'       => $this->get_avatar_url( $user->customer_email ),
			'billing_address'  => array(
				'first_name' => $user->billing_first_name,
				'last_name'  => $user->billing_last_name,
				'company'    => $user->billing_company,
				'address_1'  => $user->billing_address_1,
				'address_2'  => $user->billing_address_2,
				'city'       => $user->billing_city,
				'state'      => $user->billing_state,
				'postcode'   => $user->billing_postcode,
				'country'    => $user->billing_country,
				'email'      => $user->billing_email,
				'phone'      => $user->billing_phone,
			),
			'shipping_address' => array(
				'first_name' => $user->shipping_first_name,
				'last_name'  => $user->shipping_last_name,
				'company'    => $user->shipping_company,
				'address_1'  => $user->shipping_address_1,
				'address_2'  => $user->shipping_address_2,
				'city'       => $user->shipping_city,
				'state'      => $user->shipping_state,
				'postcode'   => $user->shipping_postcode,
				'country'    => $user->shipping_country,
			),
		);


		// Allow dot notation for nested array so that user can specifies field
		// like 'billing_address.first_name'.
		return $this->flatten_array( $customer );
	}

	/**
	 * Wrapper for @see get_avatar() which doesn't simply return
	 * the URL so we need to pluck it from the HTML img tag
	 *
	 * Kudos to https://github.com/WP-API/WP-API for offering a better solution
	 *
	 * @since  2.5.0
	 * @param  string $email the customer's email
	 * @return string the URL to the customer's avatar
	 */
	protected function get_avatar_url( $email ) {
		$avatar_html = get_avatar( $email );

		// Get the URL of the avatar from the provided HTML
		preg_match( '/src=["|\'](.+)[\&|"|\']/U', $avatar_html, $matches );

		if ( isset( $matches[1] ) && ! empty( $matches[1] ) ) {
			return esc_url_raw( $matches[1] );
		}

		return null;
	}

	/**
	 * Add/Update customer data.
	 *
	 * @since 2.5.0
	 * @param int   $id   The customer ID
	 * @param array $data
	 */
	protected function update_customer_data( $id, $data ) {
		// Customer first name.
		if ( isset( $data['first_name'] ) ) {
			update_user_meta( $id, 'first_name', wc_clean( $data['first_name'] ) );
		}

		// Customer last name.
		if ( isset( $data['last_name'] ) ) {
			update_user_meta( $id, 'last_name', wc_clean( $data['last_name'] ) );
		}

		// Customer billing address.
		if ( isset( $data['billing_address'] ) ) {
			foreach ( $this->get_customer_billing_address_fields() as $address ) {
				if ( isset( $data['billing_address'][ $address ] ) ) {
					update_user_meta( $id, 'billing_' . $address, wc_clean( $data['billing_address'][ $address ] ) );
				}
			}
		}

		// Customer shipping address.
		if ( isset( $data['shipping_address'] ) ) {
			foreach ( $this->get_customer_shipping_address_fields() as $address ) {
				if ( isset( $data['shipping_address'][ $address ] ) ) {
					update_user_meta( $id, 'shipping_' . $address, wc_clean( $data['shipping_address'][ $address ] ) );
				}
			}
		}

		do_action( 'woocommerce_cli_update_customer_data', $id, $data );
	}

	/**
	 * Get customer billing address fields.
	 *
	 * @since  2.5.0
	 * @return array
	 */
	protected function get_customer_billing_address_fields() {
		return apply_filters( 'woocommerce_cli_customer_billing_address_fields', array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
			'email',
			'phone',
		) );
	}

	/**
	 * Get customer shipping address fields.
	 *
	 * @since  2.5.0
	 * @return array
	 */
	protected function get_customer_shipping_address_fields() {
		return apply_filters( 'woocommerce_cli_customer_shipping_address_fields', array(
			'first_name',
			'last_name',
			'company',
			'address_1',
			'address_2',
			'city',
			'state',
			'postcode',
			'country',
		) );
	}

	/**
	 * Get customer download fields.
	 *
	 * @since  2.5.0
	 * @return array
	 */
	protected function get_customer_download_fields() {
		return apply_filters( 'woocommerce_cli_customer_download_fields', array(
			'download_id',
			'download_name',
			'access_expires',
		) );
	}
}
