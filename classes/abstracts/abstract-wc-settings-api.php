<?php
/**
 * Admin Settings API used by Shipping Methods and Payment Gateways
 *
 * @class 		WC_Settings_API
 * @version		2.0.0
 * @package		WooCommerce/Abstracts
 * @category	Abstract Class
 * @author 		WooThemes
 */
abstract class WC_Settings_API {

	/** @var string The plugin ID. Used for option names. */
	public $plugin_id = 'woocommerce_';

	/** @var array Array of setting values. */
	public $settings = array();

	/** @var array Array of form option fields. */
	public $form_fields = array();

	/** @var array Array of validation errors. */
	public $errors = array();

	/** @var array Sanitized fields after validation. */
	public $sanitized_fields = array();

	/**
	 * Admin Options
	 *
	 * Setup the gateway settings screen.
	 * Override this in your gateway.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function admin_options() { ?>
		<h3><?php echo ( ! empty( $this->method_title ) ) ? $this->method_title : __( 'Settings', 'woocommerce' ) ; ?></h3>

		<?php echo ( ! empty( $this->method_description ) ) ? wpautop( $this->method_description ) : ''; ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table><?php
	}


	/**
	 * Initialise Settings Form Fields
	 *
	 * Add an array of fields to be displayed
	 * on the gateway's settings screen.
	 *
	 * @since 1.0.0
	 * @access public
	 * @return string
	 */
	public function init_form_fields() {}


	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool
	 */
    public function process_admin_options() {
    	$this->validate_settings_fields();

    	if ( count( $this->errors ) > 0 ) {
    		$this->display_errors();
    		return false;
    	} else {
    		update_option( $this->plugin_id . $this->id . '_settings', apply_filters( 'woocommerce_settings_api_sanitized_fields_' . $this->id, $this->sanitized_fields ) );
    		return true;
    	}
    }


    /**
     * Display admin error messages.
     *
     * @since 1.0.0
	 * @access public
	 * @return void
	 */
    public function display_errors() {}


	/**
     * Initialise Gateway Settings
     *
     * Store all settings in a single database entry
     * and make sure the $settings array is either the default
     * or the settings stored in the database.
     *
     * @since 1.0.0
     * @uses get_option(), add_option()
	 * @access public
	 * @return void
	 */
    public function init_settings() {

	    if ( ! empty( $this->settings ) )
	    	return;

    	// Load form_field settings
    	$this->settings = get_option( $this->plugin_id . $this->id . '_settings', null );

    	if ( ! $this->settings || ! is_array( $this->settings ) ) {

	    	$this->settings = array();

    		// If there are no settings defined, load defaults
    		if ( $this->form_fields )
	    		foreach ( $this->form_fields as $k => $v )
	    			$this->settings[ $k ] = isset( $v['default'] ) ? $v['default'] : '';
    	}

        if ( $this->settings && is_array( $this->settings ) ) {
    	   $this->settings = array_map( array( $this, 'format_settings' ), $this->settings );
    	   $this->enabled  = isset( $this->settings['enabled'] ) && $this->settings['enabled'] == 'yes' ? 'yes' : 'no';
        }
    }


    /**
     * get_option function.
     *
     * Gets and option from the settings API, using defaults if necessary to prevent undefined notices.
     *
     * @access public
     * @param mixed $key
     * @param mixed $empty_value
     * @return void
     */
    public function get_option( $key, $empty_value = null ) {

	    if ( empty( $this->settings ) )
	    	$this->init_settings();

    	// Get option default if unset
	    if ( ! isset( $this->settings[ $key ] ) ) {
		    $this->settings[ $key ] = isset( $this->form_fields[ $key ]['default'] ) ? $this->form_fields[ $key ]['default'] : '';
	    }

	    if ( ! is_null( $empty_value ) && empty( $this->settings[ $key ] ) )
	    	$this->settings[ $key ] = $empty_value;

	    return $this->settings[ $key ];
    }


    /**
     * Decode values for settings.
     *
     * @access public
     * @param mixed $value
     * @return array
     */
    public function format_settings( $value ) {
    	return ( is_array( $value ) ) ? $value : html_entity_decode( $value );
    }


    /**
     * Generate Settings HTML.
     *
     * Generate the HTML for the fields on the "settings" screen.
     *
     * @access public
     * @param bool $form_fields (default: false)
     * @since 1.0.0
     * @uses method_exists()
	 * @access public
	 * @return string the html for the settings
     */
    public function generate_settings_html ( $form_fields = false ) {

    	if ( ! $form_fields ) {
    		$form_fields = $this->form_fields;
    	}

    	$html = '';
    	foreach ( $form_fields as $k => $v ) {
    		if ( ! isset( $v['type'] ) || ( $v['type'] == '' ) )
    			$v['type'] = 'text'; // Default to "text" field type.

    		if ( method_exists( $this, 'generate_' . $v['type'] . '_html' ) ) {
    			$html .= $this->{'generate_' . $v['type'] . '_html'}( $k, $v );
    		} else {
	    		$html .= $this->{'generate_text_html'}( $k, $v );
    		}
    	}

    	echo $html;
    }


    /**
     * Generate Text Input HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_text_html( $key, $data ) {
    	global $woocommerce;

    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	$data['placeholder'] 	= isset( $data['placeholder'] ) ? $data['placeholder'] : '';
    	$data['type'] 			= isset( $data['type'] ) ? $data['type'] : 'text';
    	$data['desc_tip']		= isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
    	$data['description']    = isset( $data['description'] ) ? $data['description'] : '';

		// Description handling
		if ( $data['desc_tip'] === true ) {
			$description = '';
			$tip         = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
			$tip         = $data['desc_tip'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
			$tip         = '';
		} else {
			$description = $tip = '';
		}

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';

			if ( $tip )
				$html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<input class="input-text regular-input ' . esc_attr( $data['class'] ) . '" type="' . esc_attr( $data['type'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" value="' . esc_attr( $this->get_option( $key ) ) . '" placeholder="' . esc_attr( $data['placeholder'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' />';

				if ( $description )
					$html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Password Input HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_password_html( $key, $data ) {
    	global $woocommerce;

    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	$data['desc_tip']		= isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
    	$data['description']    = isset( $data['description'] ) ? $data['description'] : '';

		// Description handling
		if ( $data['desc_tip'] === true ) {
			$description = '';
			$tip         = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
			$tip         = $data['desc_tip'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
			$tip         = '';
		} else {
			$description = $tip = '';
		}

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';

			if ( $tip )
				$html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<input class="input-text regular-input ' . esc_attr( $data['class'] ) . '" type="password" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" value="' . esc_attr( $this->get_option( $key ) ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' />';

				if ( $description )
					$html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Textarea HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_textarea_html( $key, $data ) {
    	global $woocommerce;

    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class']			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	$data['desc_tip']		= isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
    	$data['description']    = isset( $data['description'] ) ? $data['description'] : '';
    	$data['placeholder'] 	= isset( $data['placeholder'] ) ? $data['placeholder'] : '';

		// Description handling
		if ( $data['desc_tip'] === true ) {
			$description = '';
			$tip         = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
			$tip         = $data['desc_tip'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
			$tip         = '';
		} else {
			$description = $tip = '';
		}

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';

			if ( $tip )
				$html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<textarea rows="3" cols="20" class="input-text wide-input ' . esc_attr( $data['class'] ) . '" placeholder="' . esc_attr( $data['placeholder'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $this->get_option( $key ) ) . '</textarea>';

				if ( $description )
					$html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Checkbox HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_checkbox_html( $key, $data ) {
    	global $woocommerce;

    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['label']			= isset( $data['label'] ) ? $data['label'] : $data['title'];
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['class'] 		    = isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 		    = isset( $data['css'] ) ? $data['css'] : '';
    	$data['desc_tip']		= isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
    	$data['description']    = isset( $data['description'] ) ? $data['description'] : '';

		// Description handling
		if ( $data['desc_tip'] === true ) {
			$description = '';
			$tip         = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
			$tip         = $data['desc_tip'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
			$tip         = '';
		} else {
			$description = $tip = '';
		}

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">' . $data['title'];

			if ( $tip )
				$html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">';
				$html .= '<input style="' . esc_attr( $data['css'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" type="checkbox" value="1" ' . checked( $this->get_option( $key ), 'yes', false ) . ' class="' . esc_attr( $data['class'] ).'" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . ' /> ' . wp_kses_post( $data['label'] ) . '</label><br />' . "\n";

				if ( $description )
					$html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Select HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_select_html( $key, $data ) {
    	global $woocommerce;

    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['options'] 		= isset( $data['options'] ) ? (array) $data['options'] : array();
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	$data['desc_tip']		= isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
    	$data['description']    = isset( $data['description'] ) ? $data['description'] : '';

		// Description handling
		if ( $data['desc_tip'] === true ) {
			$description = '';
			$tip         = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
			$tip         = $data['desc_tip'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
			$tip         = '';
		} else {
			$description = $tip = '';
		}

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';

			if ( $tip )
				$html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<select name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" style="' . esc_attr( $data['css'] ) . '" class="select ' .esc_attr( $data['class'] ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . '>';

				foreach ($data['options'] as $option_key => $option_value) :
					$html .= '<option value="' . esc_attr( $option_key ) . '" '.selected( $option_key, esc_attr( $this->get_option( $key ) ), false ).'>' . esc_attr( $option_value ) . '</option>';
				endforeach;

				$html .= '</select>';

				if ( $description )
					$html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

    /**
     * Generate Multiselect HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.0.0
     * @return string
     */
    public function generate_multiselect_html( $key, $data ) {
    	global $woocommerce;

    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['disabled']		= empty( $data['disabled'] ) ? false : true;
    	$data['options'] 		= isset( $data['options'] ) ? (array) $data['options'] : array();
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';
    	$data['desc_tip']		= isset( $data['desc_tip'] ) ? $data['desc_tip'] : false;
    	$data['description']    = isset( $data['description'] ) ? $data['description'] : '';

		// Description handling
		if ( $data['desc_tip'] === true ) {
			$description = '';
			$tip         = $data['description'];
		} elseif ( ! empty( $data['desc_tip'] ) ) {
			$description = $data['description'];
			$tip         = $data['desc_tip'];
		} elseif ( ! empty( $data['description'] ) ) {
			$description = $data['description'];
			$tip         = '';
		} else {
			$description = $tip = '';
		}

    	// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $data['custom_attributes'] ) && is_array( $data['custom_attributes'] ) )
			foreach ( $data['custom_attributes'] as $attribute => $attribute_value )
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $attribute_value ) . '"';

		$html .= '<tr valign="top">' . "\n";
			$html .= '<th scope="row" class="titledesc">';
			$html .= '<label for="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '">' . wp_kses_post( $data['title'] ) . '</label>';

			if ( $tip )
				$html .= '<img class="help_tip" data-tip="' . esc_attr( $tip ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

			$html .= '</th>' . "\n";
			$html .= '<td class="forminp">' . "\n";
				$html .= '<fieldset><legend class="screen-reader-text"><span>' . wp_kses_post( $data['title'] ) . '</span></legend>' . "\n";
				$html .= '<select multiple="multiple" style="' . esc_attr( $data['css'] ) . '" class="multiselect ' . esc_attr( $data['class'] ) . '" name="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '[]" id="' . esc_attr( $this->plugin_id . $this->id . '_' . $key ) . '" ' . disabled( $data['disabled'], true, false ) . ' ' . implode( ' ', $custom_attributes ) . '>';

				foreach ( $data['options'] as $option_key => $option_value) {
					$html .= '<option value="' . esc_attr( $option_key ) . '" ' . selected( in_array( $option_key, $this->get_option( $key, array() ) ), true, false ) . '>' . esc_attr( $option_value ) . '</option>';
				}

				$html .= '</select>';

				if ( $description )
					$html .= ' <p class="description">' . wp_kses_post( $description ) . '</p>' . "\n";

			$html .= '</fieldset>';
			$html .= '</td>' . "\n";
		$html .= '</tr>' . "\n";

    	return $html;
    }

	/**
     * Generate Title HTML.
     *
     * @access public
     * @param mixed $key
     * @param mixed $data
     * @since 1.6.2
     * @return string
     */
	public function generate_title_html( $key, $data ) {
    	$html = '';

    	$data['title']			= isset( $data['title'] ) ? $data['title'] : '';
    	$data['class'] 			= isset( $data['class'] ) ? $data['class'] : '';
    	$data['css'] 			= isset( $data['css'] ) ? $data['css'] : '';

		$html .= '</table>' . "\n";
			$html .= '<h4 class="' . esc_attr( $data['class'] ) . '">' . wp_kses_post( $data['title'] ) . '</h4>' . "\n";
			if ( isset( $data['description'] ) && $data['description'] != '' ) { $html .= '<p>' . wp_kses_post( $data['description'] ) . '</p>' . "\n"; }
		$html .= '<table class="form-table">' . "\n";

    	return $html;
    }


    /**
     * Validate Settings Field Data.
     *
     * Validate the data on the "Settings" form.
     *
     * @since 1.0.0
     * @uses method_exists()
     * @param bool $form_fields (default: false)
     * @return void
     */
    public function validate_settings_fields( $form_fields = false ) {

    	if ( ! $form_fields )
    		$form_fields = $this->form_fields;

    	$this->sanitized_fields = array();

    	foreach ( $form_fields as $k => $v ) {
    		if ( empty( $v['type'] ) )
    			$v['type'] = 'text'; // Default to "text" field type.

    		if ( method_exists( $this, 'validate_' . $v['type'] . '_field' ) ) {
    			$field = $this->{'validate_' . $v['type'] . '_field'}( $k );
    			$this->sanitized_fields[ $k ] = $field;
    		} else {
    			$field = $this->{'validate_text_field'}( $k );
    			$this->sanitized_fields[ $k ] = $field;
    		}
    	}
    }


    /**
     * Validate Checkbox Field.
     *
     * If not set, return "no", otherwise return "yes".
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    public function validate_checkbox_field( $key ) {
    	$status = 'no';
    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) && ( 1 == $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$status = 'yes';
    	}

    	return $status;
    }

    /**
     * Validate Text Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    public function validate_text_field( $key ) {
    	$text = $this->get_option( $key );

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$text = wp_kses_post( trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) );
    	}

    	return $text;
    }


    /**
     * Validate Password Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    public function validate_password_field( $key ) {
    	$text = $this->get_option( $key );

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$text = woocommerce_clean( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
    	}

    	return $text;
    }


    /**
     * Validate Textarea Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    public function validate_textarea_field( $key ) {
    	$text = $this->get_option( $key );

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$text = wp_kses( trim( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ),
    			array_merge(
    				array(
    					'iframe' => array( 'src' => true, 'style' => true, 'id' => true, 'class' => true )
    				),
    				wp_kses_allowed_html( 'post' )
    			)
    		);
    	}

    	return $text;
    }


    /**
     * Validate Select Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    public function validate_select_field( $key ) {
    	$value = $this->get_option( $key );

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$value = woocommerce_clean( stripslashes( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
    	}

    	return $value;
    }

    /**
     * Validate Multiselect Field.
     *
     * Make sure the data is escaped correctly, etc.
     *
     * @access public
     * @param mixed $key
     * @since 1.0.0
     * @return string
     */
    public function validate_multiselect_field( $key ) {
    	$value = $this->get_option( $key );

    	if ( isset( $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) ) {
    		$value = array_map( 'woocommerce_clean', array_map( 'stripslashes', (array) $_POST[ $this->plugin_id . $this->id . '_' . $key ] ) );
    	} else {
	    	$value = '';
    	}

    	return $value;
    }

}