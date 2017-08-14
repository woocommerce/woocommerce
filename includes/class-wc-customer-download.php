<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class for customer download permissions.
 *
 * @version     3.0.0
 * @since       3.0.0
 * @package     WooCommerce/Classes
 * @author      WooThemes
 */
class WC_Customer_Download extends WC_Data implements ArrayAccess {

	/**
	 * This is the name of this object type.
	 * @var string
	 */
	protected $object_type = 'customer_download';

	/**
	 * Download Data array.
	 *
	 * @since 3.0.0
	 * @var array
	 */
	protected $data = array(
		'download_id'         => '',
		'product_id'          => 0,
		'user_id'             => 0,
		'user_email'          => '',
		'order_id'            => 0,
		'order_key'           => '',
		'downloads_remaining' => '',
		'access_granted'      => null,
		'access_expires'      => null,
		'download_count'      => 0,
	);

	/**
	 * Constructor.
	 *
	 * @param int|object|array $download
	 */
	 public function __construct( $download = 0 ) {
		parent::__construct( $download );

		if ( is_numeric( $download ) && $download > 0 ) {
			$this->set_id( $download );
		} elseif ( $download instanceof self ) {
			$this->set_id( $download->get_id() );
		} elseif ( is_object( $download ) && ! empty( $download->permission_id ) ) {
			$this->set_id( $download->permission_id );
			$this->set_props( (array) $download );
			$this->set_object_read( true );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'customer-download' );

		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
 	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get download id.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_download_id( $context = 'view' ) {
		return $this->get_prop( 'download_id', $context );
	}

	/**
	 * Get product id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_product_id( $context = 'view' ) {
		return $this->get_prop( 'product_id', $context );
	}

	/**
	 * Get user id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_user_id( $context = 'view' ) {
		return $this->get_prop( 'user_id', $context );
	}

	/**
	 * Get user_email.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_user_email( $context = 'view' ) {
		return $this->get_prop( 'user_email', $context );
	}

	/**
	 * Get order_id.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_order_id( $context = 'view' ) {
		return $this->get_prop( 'order_id', $context );
	}

	/**
	 * Get order_key.
	 *
	 * @param  string $context
	 * @return string
	 */
	public function get_order_key( $context = 'view' ) {
		return $this->get_prop( 'order_key', $context );
	}

	/**
	 * Get downloads_remaining.
	 *
	 * @param  string $context
	 * @return integer|string
	 */
	public function get_downloads_remaining( $context = 'view' ) {
		return $this->get_prop( 'downloads_remaining', $context );
	}

	/**
	 * Get access_granted.
	 *
	 * @param  string $context
	 * @return WC_DateTime|null Object if the date is set or null if there is no date.
	 */
	public function get_access_granted( $context = 'view' ) {
		return $this->get_prop( 'access_granted', $context );
	}

	/**
	 * Get access_expires.
	 *
	 * @param  string $context
	 * @return WC_DateTime|null Object if the date is set or null if there is no date.
	 */
	public function get_access_expires( $context = 'view' ) {
		return $this->get_prop( 'access_expires', $context );
	}

	/**
	 * Get download_count.
	 *
	 * @param  string $context
	 * @return integer
	 */
	public function get_download_count( $context = 'view' ) {
		return $this->get_prop( 'download_count', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set download id.
	 *
	 * @param string $value
	 */
	public function set_download_id( $value ) {
		$this->set_prop( 'download_id', $value );
	}
	/**
	 * Set product id.
	 *
	 * @param int $value
	 */
	public function set_product_id( $value ) {
		$this->set_prop( 'product_id', absint( $value ) );
	}

	/**
	 * Get user id.
	 *
	 * @param int $value
	 */
	public function set_user_id( $value ) {
		$this->set_prop( 'user_id', absint( $value ) );
	}

	/**
	 * Get user_email.
	 *
	 * @param int $value
	 */
	public function set_user_email( $value ) {
		$this->set_prop( 'user_email', sanitize_email( $value ) );
	}

	/**
	 * Get order_id.
	 *
	 * @param int $value
	 */
	public function set_order_id( $value ) {
		$this->set_prop( 'order_id', absint( $value ) );
	}

	/**
	 * Get order_key.
	 *
	 * @param string $value
	 */
	public function set_order_key( $value ) {
		$this->set_prop( 'order_key', $value );
	}

	/**
	 * Get downloads_remaining.
	 *
	 * @param integer|string $value
	 */
	public function set_downloads_remaining( $value ) {
		$this->set_prop( 'downloads_remaining', '' === $value ? '' : absint( $value ) );
	}

	/**
	 * Get access_granted.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_access_granted( $date = null ) {
		$this->set_date_prop( 'access_granted', $date );
	}

	/**
	 * Get access_expires.
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_access_expires( $date = null ) {
		$this->set_date_prop( 'access_expires', $date );
	}

	/**
	 * Get download_count.
	 *
	 * @param int $value
	 */
	public function set_download_count( $value ) {
		$this->set_prop( 'download_count', absint( $value ) );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD methods
	|--------------------------------------------------------------------------
	*/

	/**
	 * Save data to the database.
	 * @since 3.0.0
	 * @return int Item ID
	 */
	public function save() {
		if ( $this->data_store ) {
			// Trigger action before saving to the DB. Use a pointer to adjust object props before save.
			do_action( 'woocommerce_before_' . $this->object_type . '_object_save', $this, $this->data_store );

			if ( $this->get_id() ) {
				$this->data_store->update( $this );
			} else {
				$this->data_store->create( $this );
			}
		}
		return $this->get_id();
	}

	/*
	|--------------------------------------------------------------------------
	| ArrayAccess/Backwards compatibility.
	|--------------------------------------------------------------------------
	*/

	/**
	 * offsetGet
	 * @param string $offset
	 * @return mixed
	 */
	public function offsetGet( $offset ) {
		if ( is_callable( array( $this, "get_$offset" ) ) ) {
			return $this->{"get_$offset"}();
		}
	}

	/**
	 * offsetSet
	 * @param string $offset
	 * @param mixed $value
	 */
	public function offsetSet( $offset, $value ) {
		if ( is_callable( array( $this, "set_$offset" ) ) ) {
			$this->{"set_$offset"}( $value );
		}
	}

	/**
	 * offsetUnset
	 * @param string $offset
	 */
	public function offsetUnset( $offset ) {
		if ( is_callable( array( $this, "set_$offset" ) ) ) {
			$this->{"set_$offset"}( '' );
		}
	}

	/**
	 * offsetExists
	 * @param string $offset
	 * @return bool
	 */
	public function offsetExists( $offset ) {
		return in_array( $offset, array_keys( $this->data ) );
	}

	/**
	 * Magic __isset method for backwards compatibility. Legacy properties which could be accessed directly in the past.
	 *
	 * @param  string $key Key name.
	 * @return bool
	 */
	public function __isset( $key ) {
		return in_array( $offset, array_keys( $this->data ) );
	}

	/**
	 * Magic __get method for backwards compatibility. Maps legacy vars to new getters.
	 *
	 * @param  string $key Key name.
	 * @return mixed
	 */
	public function __get( $key ) {
		if ( is_callable( array( $this, "get_$key" ) ) ) {
			return $this->{"get_$key"}( '' );
		}
	}
}
