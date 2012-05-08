<?php
class WooThemes_Transmitter {
	var $client_key;
	private $token;
	private $app_keys;
	private $api_url;
	private $assets_url;
	private $transient_expire_time;
	private $is_enabled;
	
	/**
	 * Constructor.
	 * @return {void}
	 */
	public function __construct ( $file ) {
		$this->client_key = '829d3e65-fe23-4c2f-902b-15e50646aa81';
		$this->token = 'wootransmitter';
		$this->api_url = 'http://api.transmitterapp.com/v1/';
		$this->assets_url = $this->get_assets_url( $file );
		$this->transient_expire_time = 60 * 60 * 24; // 24 Hours.
		$this->app_keys = array();
		$this->is_enabled = get_option( $this->token . '-status', false );

		// Initialise the script.
		add_action( 'init', array( &$this, 'init' ) );

		add_action( 'wp_ajax_' . $this->token . '-mark-as-read', array( &$this, 'mark_message_as_read' ) );
		add_action( 'wp_ajax_' . $this->token . '-toggle-notification-status', array( &$this, 'toggle_notifications_status' ) );
	} // End __construct()

	/**
	 * init function.
	 * 
	 * @access public
	 * @since 1.0.0
	 */
	public function init () {
		if ( current_user_can( 'manage_options' ) ) {
			add_action( 'admin_bar_menu', array( &$this, 'add_toolbar_menu' ) );
			add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ), 10 );
			add_action( 'admin_print_styles', array( &$this, 'enqueue_styles' ), 10 );
		}
	} // End init()

	/**
	 * toggle_notifications_status function.
	 * 
	 * @access public
	 * @since 1.0.0
	 */
	public function toggle_notifications_status () {
		if( ! current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'wootransmitter' ) );
		
		if( ! check_admin_referer( $this->token . '-toggle-notification-status' ) ) wp_die( __( 'You have taken too long. Please go back and retry.', 'wootransmitter' ) );
		
		$status = isset( $_GET['status'] ) && in_array( $_GET['status'], array( 'enable', 'disable' ) ) ? $_GET['status'] : '';
		
		if( ! $status ) die;

		if ( $this->is_enabled == true ) {
			$new_status = 0;
		} else {
			$new_status = 1;
		}

		// Run the update.
		update_option( $this->token . '-status', $new_status );

		$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() );
		wp_safe_redirect( $sendback );
		exit;
	} // End toggle_notifications_status()

	/**
	 * mark_message_as_read function.
	 * 
	 * @access public
	 * @since 1.0.0
	 */
	public function mark_message_as_read () {
		if( ! current_user_can( 'manage_options' ) ) wp_die( __( 'You do not have sufficient permissions to access this page.', 'wootransmitter' ) );
		
		if( ! check_admin_referer( $this->token . '-mark-as-read' ) ) wp_die( __( 'You have taken too long. Please go back and retry.', 'wootransmitter' ) );
		
		$message_id = isset( $_GET['message_id'] ) && (int)$_GET['message_id'] ? (int)$_GET['message_id'] : '';
		
		if( ! $message_id ) die;

		$read_messages = get_option( $this->token . '-read', array() );
		$active_message_ids = $this->get_message_ids();

		// Remove expired read messages.
		$read_messages = $this->remove_expired_read_messages( $read_messages, $active_message_ids );

		if ( ! in_array( $message_id, $read_messages ) ) {
			$read_messages[] = $message_id;

			// Run the update.
			update_option( $this->token . '-read', $read_messages );
		}

		$sendback = remove_query_arg( array( 'trashed', 'untrashed', 'deleted', 'ids' ), wp_get_referer() );
		wp_safe_redirect( $sendback );
		exit;
	} // End mark_message_as_read()

	/**
	 * remove_expired_read_messages function.
	 * 
	 * @access public
	 * @since 1.0.0
	 */
	public function remove_expired_read_messages ( $read, $active ) {
		foreach ( $read as $k => $v ) {
			if ( ! in_array( $v, $active ) ) {
				unset( $read[$k] );
			}
		}
		return $read;
	} // End remove_expired_read_messages()

	/**
	 * add_toolbar_menu function.
	 * 
	 * @access public
	 * @since 1.0.0
	 */
	public function add_toolbar_menu () {
		global $wp_admin_bar, $current_user;

		if ( $this->is_enabled ) {
			$total_unread = $this->count_messages( 'unread' );
			$messages = $this->get_messages( 'unread' );

			$class = 'message-count';
			if ( intval( $total_unread ) > 0 ) { $class .= ' unread'; } else { $class .= ' read'; }

			$menu_title = '<span class="' . $class . '">' . $total_unread . '</span>';
		} else {
			$menu_title = '<span class="message-count read off">' . __( 'Off', 'wootransmitter' ) . '</span>';
		}

		// Main Menu Item
		$wp_admin_bar->add_menu( array( 'id' => $this->token, 'title' => $menu_title, 'href' => '#' ) );

		// Begin Sub-Menu
		$wp_admin_bar->add_group( array( 'parent' => $this->token, 'id' => $this->token . '-messages' ) );

		// Mark as read URL.
		$redirection_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		$delimeter = '?';
		if ( stristr( $redirection_url, '?' ) ) { $delimeter = '&'; }

		if ( $this->is_enabled ) {
			// Add Messages
			// $k = key
			// $v = full data (including app data)
			// $i = message key
			// $j = message data

			if ( $total_unread > 0 ) {
				$length = 100;

				foreach ( $messages as $k => $v ) {
					if ( isset( $v->messages ) && count( $v->messages ) > 0 ) {
						$count = 0;
						foreach ( $v->messages as $i => $j ) {
							$count++;

							$class = '';
							if ( $count == $total_unread ) { $class = 'last'; }

							$mark_as_read = wp_nonce_url( admin_url( 'admin-ajax.php?action=' . $this->token . '-mark-as-read&message_id=' . $j->id ), $this->token . '-mark-as-read' );

							$excerpt = substr( $j->message, 0, $length );
							if ( strlen( $j->message ) > $length ) {
								$excerpt .= '...';
							}

							$wp_admin_bar->add_menu( array( 'parent' => $this->token . '-messages', 'id' => 'message-id-' . ( $j->id ), 'title' => '<a href="' . esc_url( $mark_as_read ) . '" class="mark-as-read" title="' . esc_attr__( 'Mark as Read', 'wootransmitter' ) . '">' . __( 'Mark as Read', 'wootransmitter' ) . '</a><span class="message-contents"><span class="title">' . $j->title . '</span><span class="excerpt">' . $excerpt . '</span><span class="application ' . sanitize_title_with_dashes( $v->application->name ) . '">' . $v->application->name . '</span></span>' ) );

							$wp_admin_bar->add_menu( array( 'parent' => 'message-id-' . ( $j->id ), 'id' => 'message-id-' . ( $j->id ) . '-full', 'title' => '<span class="message-contents"><span class="excerpt">' . nl2br( $j->message ) . '</span></span>' ) );
						}
					}
				}
			} else {
				$wp_admin_bar->add_menu( array( 'parent' => $this->token . '-messages', 'id' => 'no-unread-messages', 'title' => '<span class="title">' . __( 'You have no unread messages!', 'wootransmitter' ) . '</span>' ) );
			}
		} else {
			$wp_admin_bar->add_menu( array( 'parent' => $this->token . '-messages', 'id' => 'notifications-disabled', 'title' => '<span class="title">' . __( 'Notifications are disabled.', 'wootransmitter' ) . '</span>' ) );
		}

		if ( $this->is_enabled ) {
			$status_text = __( 'Disable notifications?', 'wootransmitter' );
			$status_action = 'disable';
		} else {
			$status_text = __( 'Enable notifications?', 'wootransmitter' );
			$status_action = 'enable';
		}

		$change_status_url = wp_nonce_url( admin_url( 'admin-ajax.php?action=' . $this->token . '-toggle-notification-status&status=' . $status_action ), $this->token . '-toggle-notification-status' );

		$wp_admin_bar->add_menu( array( 'parent' => $this->token . '-messages', 'id' => 'notificatiion-status', 'title' => '<span class="title"><a href="' . esc_url( $change_status_url ) . '">' . $status_text . '</a></span>' ) );
	} // End add_toolbar_menu()

	/**
	 * add_app_key function.
	 * 
	 * Add an app key into the array, to be processed with the other keys.
	 * @param string $key The key to be added.
	 * @param string $version The version number of the app.
	 */
	public function add_app_key ( $key, $version ) {
		if ( $key != '' && $version != '' && ! in_array( $key, array_keys( $this->app_keys ) ) ) { $this->app_keys[$key] = $version; }
	} // End add_app_key()

	/**
	 * remove_app_key function.
	 * 
	 * Remove an app key from the array of app keys.
	 * @param string $key The key to be removed.
	 */
	public function remove_app_key ( $key ) {
		if ( $key != '' && in_array( $key, array_keys( $this->app_keys ) ) ) { unset( $this->app_keys[$key] ); }
	} // End add_app_key()

	/**
	 * get_messages function.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @param $status ( all | unread )
	 * @return array $data
	 */
	public function get_messages ( $status = 'all' ) {
		$transient_key = $this->token . '-messages';

		if ( false === ( $data = get_transient( $transient_key ) ) ) {
			$response = $this->request( '/messages' );
			
			if ( isset( $response->error ) && ( $response->error != '' ) ) {
				$this->log_request_error( $response->error );
			} else {
				$data = $response;
				set_transient( $transient_key, $data, $this->transient_expire_time );
			}
		}

		// Display only unread, if $status = 'unread'.
		if ( $status == 'unread' ) {
			$read_messages = get_option( $this->token . '-read', array() );
			foreach ( $data as $k => $v ) {
				foreach ( $v->messages as $i => $j ) {
					if ( in_array( $j->id, $read_messages ) ) {
						unset( $data[$k]->messages[$i] );
					}
				}
			}
		}

		return $data;
	} // End get_messages()

	/**
	 * get_message_ids function.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @param string $status ( all | unread )
	 * @return array $ids
	 */
	private function get_message_ids ( $status = 'all' ) {
		$ids = array();
		$data = $this->get_messages( $status );

		if ( is_array( $data ) && count( $data ) > 0 ) {
			foreach ( $data as $k => $v ) {
				if ( isset( $v->messages ) && count( $v->messages ) > 0 ) {
					foreach ( $v->messages as $i => $j ) {
						if ( isset( $j->id ) && ( $j->id != '' ) ) {
							$ids[] = $j->id;
						}
					}
				}
			}
		}
		return $ids;
	} // End get_message_ids()

	/**
	 * count_messages function.
	 * 
	 * @access public
	 * @since 1.0.0
	 * @param string $status ( all | unread )
	 * @return int $total
	 */
	private function count_messages ( $status = 'all' ) {
		$total = 0;
		$data = $this->get_messages( $status );

		if ( is_array( $data ) && count( $data ) > 0 ) {
			foreach ( $data as $k => $v ) {
				if ( isset( $v->messages ) && count( $v->messages ) > 0 ) {
					$total += count( $v->messages );
				}
			}
		}
		return $total;
	} // End count_messages()

	/**
	 * request function.
	 * 
	 * @access private
	 * @since 1.0.0
	 * @param string $endpoint (must include / prefix)
	 * @param array $params
	 * @return array $data
	 */
	private function request ( $endpoint = '/messages', $params = array() ) {
		$params['user_key'] = $this->client_key;
		$params['applications'] = $this->app_keys;

		$response = wp_remote_post( $this->api_url . $endpoint, array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => $params,
			'cookies' => array()
		    )
		);

		if( is_wp_error( $response ) ) {
		  $data = new StdClass();
		  $data->error = __( 'WooTransmitter Request Error', 'woothemes' );
		} else {
			$data = $response['body'];
		}
		
		$data = json_decode( $data );

		// Store errors in a transient, to be cleared on each request.
		if ( isset( $data->error ) && ( $data->error != '' ) ) {
			$this->log_request_error( $data->error );
		}
		
		return $data;
	} // End request()

	/**
	 * log_request_error function.
	 * 
	 * @access private
	 * @since 1.0.0
	 * @param string $error
	 */
	private function log_request_error ( $error ) {
		set_transient( $this->token . '-request-error', $error );
	} // End log_request_error()

	/**
	 * enqueue_styles function.
	 * 
	 * @access public
	 * @return void
	 */
	public function enqueue_styles () {
		wp_register_style( $this->token, $this->assets_url . 'css/style.css', 'screen', '1.0.3' );
		wp_enqueue_style( $this->token );
	} // End enqueue_styles()

	/**
	 * get_assets_url function.
	 * 
	 * @description Get an "/assets/" URL from a specified file path.
	 * @access public
	 * @param string $file
	 * @return void
	 */
	private function get_assets_url ( $file ) {
		$url = '';
		$bits = explode( 'wp-content', $file );
		$url = trailingslashit( WP_CONTENT_URL . trailingslashit( dirname( $bits[1] ) ) . 'assets' );
		return $url;
	} // End get_assets_url()

	/**
	 * clean_url_params function.
	 * 
	 * @description Strip any trailing & or ? from the given string.
	 * @access public
	 * @param string $url
	 * @return string $url
	 */
	private function clean_url_params ( $url ) {
		return $url;
	} // End clean_url_params()
} // End Class
?>