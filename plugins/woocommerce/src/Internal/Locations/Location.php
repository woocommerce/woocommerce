<?php
/**
 * Location class file.
 */

declare( strict_types = 1 );

namespace Automattic\WooCommerce\Internal\Locations;

defined( 'ABSPATH' ) || exit;

/**
 * A single inventory location.
 *
 * @internal
 */
class Location extends \WC_Data {

	/**
	 * Allowed location types (M1).
	 */
	public const ALLOWED_TYPES = array( 'pos' );

	/**
	 * Object type.
	 *
	 * @var string
	 */
	protected $object_type = 'location';

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'locations';

	/**
	 * Default data. GMT datetimes are stored as 'Y-m-d H:i:s' strings.
	 *
	 * @var array
	 */
	protected $data = array(
		'name'         => '',
		'type'         => '',
		'address_1'    => '',
		'address_2'    => '',
		'city'         => '',
		'state'        => '',
		'postcode'     => '',
		'country'      => '',
		'date_created' => null,
		'date_deleted' => null,
	);

	/**
	 * Constructor.
	 *
	 * @param int|Location $data Location id or object.
	 */
	public function __construct( $data = 0 ) {
		parent::__construct( $data );

		if ( $data instanceof self ) {
			$this->set_id( $data->get_id() );
		} elseif ( is_numeric( $data ) && $data > 0 ) {
			$this->set_id( (int) $data );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = \WC_Data_Store::load( 'location' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/**
	 * Get name.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_name( string $context = 'view' ): string {
		return (string) $this->get_prop( 'name', $context );
	}

	/**
	 * Get type.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_type( string $context = 'view' ): string {
		return (string) $this->get_prop( 'type', $context );
	}

	/**
	 * Get address line 1.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_address_1( string $context = 'view' ): string {
		return (string) $this->get_prop( 'address_1', $context );
	}

	/**
	 * Get address line 2.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_address_2( string $context = 'view' ): string {
		return (string) $this->get_prop( 'address_2', $context );
	}

	/**
	 * Get city.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_city( string $context = 'view' ): string {
		return (string) $this->get_prop( 'city', $context );
	}

	/**
	 * Get state.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_state( string $context = 'view' ): string {
		return (string) $this->get_prop( 'state', $context );
	}

	/**
	 * Get postcode.
	 *
	 * @param string $context View or edit context.
	 */
	public function get_postcode( string $context = 'view' ): string {
		return (string) $this->get_prop( 'postcode', $context );
	}

	/**
	 * Get country (ISO-2).
	 *
	 * @param string $context View or edit context.
	 */
	public function get_country( string $context = 'view' ): string {
		return (string) $this->get_prop( 'country', $context );
	}

	/**
	 * Get created date (GMT 'Y-m-d H:i:s' string or null).
	 *
	 * @param string $context View or edit context.
	 * @return string|null
	 */
	public function get_date_created( string $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get deleted date (GMT 'Y-m-d H:i:s' string or null).
	 *
	 * @param string $context View or edit context.
	 * @return string|null
	 */
	public function get_date_deleted( string $context = 'view' ) {
		return $this->get_prop( 'date_deleted', $context );
	}

	/**
	 * Set name.
	 *
	 * @param string $name Name.
	 */
	public function set_name( string $name ): void {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set type. Validates against the allowlist.
	 *
	 * @param string $type Type.
	 * @throws \WC_Data_Exception When the type is not allowed.
	 */
	public function set_type( string $type ): void {
		if ( ! in_array( $type, self::ALLOWED_TYPES, true ) ) {
			throw new \WC_Data_Exception(
				'woocommerce_location_invalid_type',
				sprintf(
					/* translators: %s: the invalid location type. */
					esc_html__( 'Invalid location type: %s.', 'woocommerce' ),
					esc_html( $type )
				)
			);
		}
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set address line 1.
	 *
	 * @param string $value Value.
	 */
	public function set_address_1( string $value ): void {
		$this->set_prop( 'address_1', $value );
	}

	/**
	 * Set address line 2.
	 *
	 * @param string $value Value.
	 */
	public function set_address_2( string $value ): void {
		$this->set_prop( 'address_2', $value );
	}

	/**
	 * Set city.
	 *
	 * @param string $value Value.
	 */
	public function set_city( string $value ): void {
		$this->set_prop( 'city', $value );
	}

	/**
	 * Set state.
	 *
	 * @param string $value Value.
	 */
	public function set_state( string $value ): void {
		$this->set_prop( 'state', $value );
	}

	/**
	 * Set postcode.
	 *
	 * @param string $value Value.
	 */
	public function set_postcode( string $value ): void {
		$this->set_prop( 'postcode', $value );
	}

	/**
	 * Set country (normalised to uppercase ISO-2).
	 *
	 * @param string $value Value.
	 */
	public function set_country( string $value ): void {
		$this->set_prop( 'country', strtoupper( substr( $value, 0, 2 ) ) );
	}

	/**
	 * Set created date.
	 *
	 * @param string|null $date GMT 'Y-m-d H:i:s' string or null.
	 */
	public function set_date_created( $date ): void {
		$this->set_prop( 'date_created', $date );
	}

	/**
	 * Set deleted date.
	 *
	 * @param string|null $date GMT 'Y-m-d H:i:s' string or null.
	 */
	public function set_date_deleted( $date ): void {
		$this->set_prop( 'date_deleted', $date );
	}
}
