<?php
/**
 * WooCommerce Admin (Dashboard) Notes.
 *
 * The WooCommerce admin notes class gets admin notes data from storage and checks validity.
 *
 * @package WooCommerce/Classes
 * @version 3.0.0
 */
defined( 'ABSPATH' ) || exit;

class WC_Admin_Note extends WC_Data {

	// Note status codes
	const E_WC_ADMIN_NOTE_UNACTIONED = 'unactioned';
	const E_WC_ADMIN_NOTE_ACTIONED = 'actioned';

	// Note types
	const E_WC_ADMIN_NOTE_DISMISSABLE = 'dismissable';
	const E_WC_ADMIN_NOTE_ERROR = 'error';
	const E_WC_ADMIN_NOTE_WARNING = 'warning';
	const E_WC_ADMIN_NOTE_INFORMATIONAL = 'info';

	/**
	 * This is the name of this object type.
	 *
	 * @var string
	 */
	protected $object_type = 'admin-note';

	/**
	 * Data array, with defaults.
	 *
	 * @var array
	 */
	protected $data = array(
		'name'          => '-',
		'type'          => self::E_WC_ADMIN_NOTE_INFORMATIONAL,
		'locale'        => 'en_US',
		'title'         => '-',
		'content'       => '-',
		'icon'          => 'info',
		'content_data'  => array(),
		'status'        => self::E_WC_ADMIN_NOTE_UNACTIONED,
		'source'        => 'woocommerce',
		'date_created'  => '0000-00-00 00:00:00',
		'date_reminder' => '',
		'actions'       => array(),
	);

	/**
	 * Cache group.
	 *
	 * @var string
	 */
	protected $cache_group = 'admin-note';

	/**
	 * Note constructor. Loads note data.
	 *
	 * @param mixed $data Note data, object, or ID.
	 */
	public function __construct( $data = '' ) {
		parent::__construct( $data );

		if ( $data instanceof WC_Admin_Note ) {
			$this->set_id( absint( $data->get_id() ) );
		} elseif ( is_numeric( $data ) && 'admin-note' === get_post_type( $data ) ) {
			$this->set_id( $data );
		} elseif ( is_object( $data ) && ! empty( $data->note_id ) ) {
			$this->set_id( $data->note_id );
			$this->set_props( (array) $data );
			$this->set_object_read( true );
		} else {
			$this->set_object_read( true );
		}

		$this->data_store = WC_Data_Store::load( 'admin-notes' );
		if ( $this->get_id() > 0 ) {
			$this->data_store->read( $this );
		}
	}

	/*
	|--------------------------------------------------------------------------
	| Getters
	|--------------------------------------------------------------------------
	|
	| Methods for getting data from the note object.
	|
	*/

	/**
	 * Get note name.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get note type.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_type( $context = 'view' ) {
		return $this->get_prop( 'type', $context );
	}

	/**
	 * Get note locale.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_locale( $context = 'view' ) {
		return $this->get_prop( 'locale', $context );
	}

	/**
	 * Get note title.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_title( $context = 'view' ) {
		return $this->get_prop( 'title', $context );
	}

	/**
	 * Get note content.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_content( $context = 'view' ) {
		return $this->get_prop( 'content', $context );
	}

	/**
	 * Get note icon (Gridicon).
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_icon( $context = 'view' ) {
		return $this->get_prop( 'icon', $context );
	}

	/**
	 * Get note content data (i.e. values that would be needed for re-localization)
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_content_data( $context = 'view' ) {
		return $this->get_prop( 'content_data', $context );
	}

	/**
	 * Get note status.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get note source.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return string
	 */
	public function get_source( $context = 'view' ) {
		return $this->get_prop( 'source', $context );
	}

	/**
	 * Get date note was created.
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get date on which user should be reminded of the note (if any).
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return WC_DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_reminder( $context = 'view' ) {
		return $this->get_prop( 'date_reminder', $context );
	}

	/**
	 * Get actions on the note (if any).
	 *
	 * @param  string $context What the value is for. Valid values are 'view' and 'edit'.
	 * @return array
	 */
	public function get_actions( $context = 'view' ) {
		return $this->get_prop( 'actions', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| Setters
	|--------------------------------------------------------------------------
	|
	| Methods for setting note data. These should not update anything in the
	| database itself and should only change what is stored in the class
	| object.
	|
	*/

	/**
	 * Set note name.
	 *
	 * @param string $name Note name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set note type.
	 *
	 * @param string $type Note type.
	 */
	public function set_type( $type ) {
		$this->set_prop( 'type', $type );
	}

	/**
	 * Set note locale.
	 *
	 * @param string $locale Note locale.
	 */
	public function set_locale( $locale ) {
		$this->set_prop( 'locale', $locale );
	}

	/**
	 * Set note title.
	 *
	 * @param string $title Note title.
	 */
	public function set_title( $title ) {
		$this->set_prop( 'title', $title );
	}

	/**
	 * Set note content.
	 *
	 * @param string $content Note content.
	 */
	public function set_content( $content ) {
		$this->set_prop( 'content', $content );
	}

	/**
	 * Set note icon (Gridicon).
	 *
	 * @param string $icon Note icon.
	 */
	public function set_icon( $icon ) {
		$this->set_prop( 'icon', $icon );
	}

	/**
	 * Set note data for potential re-localization.
	 *
	 * @param array $data Note data.
	 */
	public function set_content_data( $content_data ) {
		if ( ! is_array( $content_data ) ) {
			// TODO $this->error( 'admin_note_invalid_data', __( 'Invalid note content data', 'woocommerce' ) );
		}

		$this->set_prop( 'content_data', $content_data );
	}

	/**
	 * Set note status.
	 *
	 * @param string $status Note status.
	 */
	public function set_status( $status ) {
		$this->set_prop( 'status', $status );
	}

	/**
	 * Set note source.
	 *
	 * @param string $source Note source.
	 */
	public function set_source( $source ) {
		$this->set_prop( 'source', $source );
	}

	/**
	 * Set date note was created. NULL is not allowed
	 *
	 * @param string|integer $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed.
	 */
	public function set_date_created( $date ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set date admin should be reminded of note. NULL IS allowed
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if there is no date.
	 */
	public function set_date_reminder( $date ) {
		$this->set_date_prop( 'date_reminder', $date );
	}

	/**
	 * Add an action to the note
	 */
	public function add_action( $name, $label, $query ) {
		if ( 0 !== $this->get_id() ) {
			$action = array(
				'name'  => wc_clean( $name ),
				'label' => wc_clean( $label ),
				'query' => wc_clean( $query ),
			);
			$note_actions = $this->get_prop( 'actions', 'edit' );
			$note_actions[] = (object) $action;
			$this->set_prop( 'actions', $note_actions );
		}
	}
}
