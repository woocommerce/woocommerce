<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Abstract Email Class
 *
 * WooCommerce Email Class which is extended by specific email template classes to add emails to WooCommerce
 *
 * @class 		WC_Email
 * @version		2.0.0
 * @package		WooCommerce/Abstracts
 * @author 		WooThemes
 * @category	Abstract Class
 * @extends 	WC_Settings_API
 */
abstract class WC_Email extends WC_Settings_API {

	/** @var string Payment method ID. */
	var $id;

	/** @var string Payment method title. */
	var $title;

	/** @var bool True if the method is enabled. */
	var $enabled;

	/** @var string Description for the gateway. */
	var $description;

	/** @var string plain text template path */
	var $template_plain;

	/** @var string html template path */
	var $template_html;

	/** @var string template path */
	var $template_base;

	/** @var string recipients for the email */
	var $recipient;

	/** @var string heading for the email content */
	var $heading;

	/** @var string subject for the email */
	var $subject;

	/** @var object this email is for, for example a customer, product, or email */
	var $object;

	/** @var array strings to find in subjects/headings */
	var $find;

	/** @var array strings to replace in subjects/headings */
	var $replace;

	/** @var string For multipart emails */
	var $mime_boundary;

	/** @var string For multipart emails */
	var $mime_boundary_header;

	/** @var bool true when email is being sent */
	var $sending;

	/**
     *  List of preg* regular expression patterns to search for,
     *  used in conjunction with $replace.
     *  https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
     *
     *  @var array $search
     *  @access public
     *  @see $replace
     */
    var $plain_search = array(
        "/\r/",                                  // Non-legal carriage return
        '/&(nbsp|#160);/i',                      // Non-breaking space
        '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i',
		                                         // Double quotes
        '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
        '/&gt;/i',                               // Greater-than
        '/&lt;/i',                               // Less-than
        '/&(amp|#38);/i',                        // Ampersand
        '/&(copy|#169);/i',                      // Copyright
        '/&(trade|#8482|#153);/i',               // Trademark
        '/&(reg|#174);/i',                       // Registered
        '/&(mdash|#151|#8212);/i',               // mdash
        '/&(ndash|minus|#8211|#8722);/i',        // ndash
        '/&(bull|#149|#8226);/i',                // Bullet
        '/&(pound|#163);/i',                     // Pound sign
        '/&(euro|#8364);/i',                     // Euro sign
        '/&[^&;]+;/i',                           // Unknown/unhandled entities
        '/[ ]{2,}/'                              // Runs of spaces, post-handling
    );

    /**
     *  List of pattern replacements corresponding to patterns searched.
     *
     *  @var array $replace
     *  @access public
     *  @see $search
     */
    var $plain_replace = array(
        '',                                     // Non-legal carriage return
        ' ',                                    // Non-breaking space
        '"',                                    // Double quotes
        "'",                                    // Single quotes
        '>',
        '<',
        '&',
        '(c)',
        '(tm)',
        '(R)',
        '--',
        '-',
        '*',
        '£',
        'EUR',                                  // Euro sign. € ?
        '',                                     // Unknown/unhandled entities
        ' '                                     // Runs of spaces, post-handling
    );

	/**
	 * Constructor
	 *
	 * @access public
	 * @return void
	 */
	function __construct() {
		global $woocommerce;

		// Init settings
		$this->init_form_fields();
		$this->init_settings();

		// Save settings hook
		add_action( 'woocommerce_update_options_email_' . $this->id, array( $this, 'process_admin_options' ) );

		// Default template base if not declared in child constructor
		if ( is_null( $this->template_base ) )
			$this->template_base = $woocommerce->plugin_path() . '/templates/';

		// Settings
		$this->heading 			= $this->get_option( 'heading', $this->heading );
		$this->subject      	= $this->get_option( 'subject', $this->subject );
		$this->email_type     	= $this->get_option( 'email_type' );
		$this->enabled   		= $this->get_option( 'enabled' );

		// Find/replace
		$this->find = array( '{blogname}' );
		$this->replace = array( $this->get_blogname() );

		// For multipart messages
		add_filter( 'phpmailer_init', array( $this, 'handle_multipart' ) );
	}

	/**
	 * handle_multipart function.
	 *
	 * @access public
	 * @param mixed $mailer
	 * @return void
	 */
	function handle_multipart( $mailer )  {

		if ( $this->sending && $this->get_email_type() == 'multipart' ) {

			$mailer->AltBody = wordwrap( preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) ) );
			//$mailer->AltBody = wordwrap( html_entity_decode( strip_tags( $this->get_content_plain() ) ), 70 );
			$this->sending = false;
		}

		return $mailer;
	}

	/**
	 * format_string function.
	 *
	 * @access public
	 * @param mixed $string
	 * @return string
	 */
	function format_string( $string ) {
		return str_replace( $this->find, $this->replace, $string );
	}
	/**
	 * get_subject function.
	 *
	 * @access public
	 * @return string
	 */
	function get_subject() {
		return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * get_heading function.
	 *
	 * @access public
	 * @return string
	 */
	function get_heading() {
		return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	/**
	 * get_recipient function.
	 *
	 * @access public
	 * @return string
	 */
	function get_recipient() {
		return apply_filters( 'woocommerce_email_recipient_' . $this->id, $this->recipient, $this->object );
	}

	/**
	 * get_headers function.
	 *
	 * @access public
	 * @return string
	 */
	function get_headers() {
		return apply_filters( 'woocommerce_email_headers', "Content-Type: " . $this->get_content_type() . "\r\n", $this->id, $this->object );
	}

	/**
	 * get_attachments function.
	 *
	 * @access public
	 * @return string
	 */
	function get_attachments() {
		return apply_filters( 'woocommerce_email_attachments', '', $this->id, $this->object );
	}

	/**
	 * get_type function.
	 *
	 * @access public
	 * @return string
	 */
	function get_email_type() {
		return $this->email_type ? $this->email_type : 'plain';
	}

	/**
	 * get_content_type function.
	 *
	 * @access public
	 * @return void
	 */
	function get_content_type() {
		switch ( $this->get_email_type() ) {
			case "html" :
				return 'text/html';
			case "multipart" :
				return 'multipart/alternative';
			default :
				return 'text/plain';
		}
	}

	/**
	* Proxy to parent's get_option and attempt to localize the result using gettext.
	*
	* @access public
	* @return string
	*/
	function get_option( $key, $empty_value = null ) {
		return __( parent::get_option( $key, $empty_value ) );
	}

	/**
	 * Checks if this email is enabled and will be sent.
	 *
	 * @access public
	 * @return bool
	 */
	function is_enabled() {
		$enabled = $this->enabled == "yes" ? true : false;

		return apply_filters( 'woocommerce_email_enabled_' . $this->id, $enabled, $this->object );
	}

	/**
	 * get_blogname function.
	 *
	 * @access public
	 * @return void
	 */
	function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * get_content function.
	 *
	 * @access public
	 * @return string
	 */
	function get_content() {

		$this->sending = true;

		if ( $this->get_email_type() == 'plain' ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else {
			$email_content = $this->style_inline( $this->get_content_html() );
		}

		return $email_content;
	}

	/**
	 * Apply inline styles to dynamic content.
	 *
	 * @access public
	 * @param mixed $content
	 * @return void
	 */
	function style_inline( $content ) {

		if ( ! class_exists( 'DOMDocument' ) )
			return $content;

		$dom = new DOMDocument();
		@$dom->loadHTML( $content );

		$nodes = $dom->getElementsByTagName('img');

		foreach( $nodes as $node )
			if ( ! $node->hasAttribute( 'style' ) )
				$node->setAttribute( "style", "display:inline; border:none; font-size:14px; font-weight:bold; height:auto; line-height:100%; outline:none; text-decoration:none; text-transform:capitalize;" );

		$nodes_h1 = $dom->getElementsByTagName('h1');
		$nodes_h2 = $dom->getElementsByTagName('h2');
		$nodes_h3 = $dom->getElementsByTagName('h3');

		foreach( $nodes_h1 as $node )
			if ( ! $node->hasAttribute( 'style' ) )
				$node->setAttribute( "style", "color: " . get_option( 'woocommerce_email_text_color' ) . "; display:block; font-family:Arial; font-size:34px; font-weight:bold; margin-top: 10px; margin-right:0; margin-bottom:10px; margin-left:0; text-align:left; line-height: 150%;" );

		foreach( $nodes_h2 as $node )
			if ( ! $node->hasAttribute( 'style' ) )
				$node->setAttribute( "style", "color: " . get_option( 'woocommerce_email_text_color' ) . "; display:block; font-family:Arial; font-size:30px; font-weight:bold; margin-top: 10px; margin-right:0; margin-bottom:10px; margin-left:0; text-align:left; line-height: 150%;" );

		foreach( $nodes_h3 as $node )
			if ( ! $node->hasAttribute( 'style' ) )
				$node->setAttribute( "style", "color: " . get_option( 'woocommerce_email_text_color' ) . "; display:block; font-family:Arial; font-size:26px; font-weight:bold; margin-top: 10px; margin-right:0; margin-bottom:10px; margin-left:0; text-align:left; line-height: 150%;" );

		$nodes = $dom->getElementsByTagName('a');

		foreach( $nodes as $node )
			if ( ! $node->hasAttribute( 'style' ) )
				$node->setAttribute( "style", "color: " . get_option( 'woocommerce_email_text_color' ) . "; font-weight:normal; text-decoration:underline;" );

		$content = $dom->saveHTML();

		return $content;
	}

	/**
	 * get_content_plain function.
	 *
	 * @access public
	 * @return void
	 */
	function get_content_plain() {}

	/**
	 * get_content_html function.
	 *
	 * @access public
	 * @return void
	 */
	function get_content_html() {}

	/**
	 * Get from name for email.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_name() {
		return wp_specialchars_decode( esc_html( get_option( 'woocommerce_email_from_name' ) ) );
	}

	/**
	 * Get from email address.
	 *
	 * @access public
	 * @return string
	 */
	function get_from_address() {
		return sanitize_email( get_option( 'woocommerce_email_from_address' ) );
	}

	/**
	 * Send the email.
	 *
	 * @access public
	 * @param mixed $to
	 * @param mixed $subject
	 * @param mixed $message
	 * @param string $headers
	 * @param string $attachments
	 * @return void
	 */
	function send( $to, $subject, $message, $headers, $attachments ) {
		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		wp_mail( $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );
	}

    /**
     * Initialise Settings Form Fields - these are generic email options most will use.
     *
     * @access public
     * @return void
     */
    function init_form_fields() {
    	$this->form_fields = array(
			'enabled' => array(
				'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
				'type' 			=> 'checkbox',
				'label' 		=> __( 'Enable this email notification', 'woocommerce' ),
				'default' 		=> 'yes'
			),
			'subject' => array(
				'title' 		=> __( 'Email subject', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'heading' => array(
				'title' 		=> __( 'Email heading', 'woocommerce' ),
				'type' 			=> 'text',
				'description' 	=> sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading ),
				'placeholder' 	=> '',
				'default' 		=> ''
			),
			'email_type' => array(
				'title' 		=> __( 'Email type', 'woocommerce' ),
				'type' 			=> 'select',
				'description' 	=> __( 'Choose which format of email to send.', 'woocommerce' ),
				'default' 		=> 'html',
				'class'			=> 'email_type',
				'options'		=> array(
					'plain' 		=> __( 'Plain text', 'woocommerce' ),
					'html' 			=> __( 'HTML', 'woocommerce' ),
					'multipart' 	=> __( 'Multipart', 'woocommerce' ),
				)
			)
		);
    }

	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * @since 1.0.0
	 * @access public
	 * @return bool
	 */
    public function process_admin_options() {

    	// Save regular options
    	parent::process_admin_options();

    	// Save templates
		if ( ! empty( $_POST['template_html_code'] ) && ! empty( $this->template_html ) ) {

			$saved	= false;
			$file	= get_stylesheet_directory() . '/woocommerce/' . $this->template_html;
			$code 	= stripslashes( $_POST['template_html_code'] );

			if ( is_writeable( $file ) ) {
				$f = fopen( $file, 'w+' );
				if ( $f !== FALSE ) {
    				fwrite( $f, $code );
    				fclose( $f );
    				$saved = true;
    			}
			}

			if ( ! $saved ) {
    			$redirect = add_query_arg( 'wc_error', urlencode( __( 'Could not write to template file.', 'woocommerce' ) ) );
    			wp_redirect( $redirect );
    			exit;
    		}
		}
		if ( ! empty( $_POST['template_plain_code'] ) && ! empty( $this->template_plain ) ) {

    		$saved	= false;
			$file	= get_stylesheet_directory() . '/woocommerce/' . $this->template_plain;
			$code 	= stripslashes( $_POST['template_plain_code'] );

			if ( is_writeable( $file ) ) {
				$f = fopen( $file, 'w+' );
				if ( $f !== FALSE ) {
    				fwrite( $f, $code );
    				fclose( $f );
    				$saved = true;
    			}
			}

			if ( ! $saved ) {
    			$redirect = add_query_arg( 'wc_error', __( 'Could not write to template file.', 'woocommerce' ) );
    			wp_redirect( $redirect );
    			exit;
    		}
		}
    }

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
	function admin_options() {
		global $woocommerce;

		// Handle any actions
		if ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) {

			if ( ! empty( $_GET['move_template'] ) && ( $template = esc_attr( basename( $_GET['move_template'] ) ) ) ) {
				if ( ! empty( $this->$template ) ) {
					if (  wp_mkdir_p( dirname( get_stylesheet_directory() . '/woocommerce/' . $this->$template ) ) && ! file_exists( get_stylesheet_directory() . '/woocommerce/' . $this->$template ) ) {
						copy( $this->template_base . $this->$template, get_stylesheet_directory() . '/woocommerce/' . $this->$template );
						echo '<div class="updated fade"><p>' . __( 'Template file copied to theme.', 'woocommerce' ) . '</p></div>';
					}
				}
			}

			if ( ! empty( $_GET['delete_template'] ) && ( $template = esc_attr( basename( $_GET['delete_template'] ) ) ) ) {
				if ( ! empty( $this->$template ) ) {
					if ( file_exists( get_stylesheet_directory() . '/woocommerce/' . $this->$template ) ) {
						unlink( get_stylesheet_directory() . '/woocommerce/' . $this->$template );
						echo '<div class="updated fade"><p>' . __( 'Template file deleted from theme.', 'woocommerce' ) . '</p></div>';
					}
				}
			}

		}

		?>
		<h3><?php echo ( ! empty( $this->title ) ) ? $this->title : __( 'Settings','woocommerce' ) ; ?></h3>

		<?php echo ( ! empty( $this->description ) ) ? wpautop( $this->description ) : ''; ?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>

		<?php if ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) { ?>
			<div id="template">
			<?php
				$templates = array(
					'template_html' 	=> __( 'HTML template', 'woocommerce' ),
					'template_plain' 	=> __( 'Plain text template', 'woocommerce' )
				);
				foreach ( $templates as $template => $title ) :
					if ( empty( $this->$template ) )
						continue;

					$local_file = get_stylesheet_directory() . '/woocommerce/' . $this->$template;
					$core_file 	= $this->template_base . $this->$template;
					?>
					<div class="template <?php echo $template; ?>">

						<h4><?php echo wp_kses_post( $title ); ?></h4>

						<?php if ( file_exists( $local_file ) ) : ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( is_writable( $local_file ) ) : ?>
									<a href="<?php echo remove_query_arg( array( 'move_template', 'saved' ), add_query_arg( 'delete_template', $template ) ); ?>" class="delete_template button"><?php _e( 'Delete template file', 'woocommerce' ); ?></a>
								<?php endif; ?>

								<?php printf( __( 'This template has been overridden by your theme and can be found in: <code>%s</code>.', 'woocommerce' ), 'yourtheme/woocommerce/' . $this->$template ); ?>
							</p>

							<div class="editor" style="display:none">

								<textarea class="code" cols="25" rows="20" <?php if ( ! is_writable( $local_file ) ) : ?>readonly="readonly" disabled="disabled"<?php else : ?>data-name="<?php echo $template . '_code'; ?>"<?php endif; ?>><?php echo file_get_contents( $local_file ); ?></textarea>

							</div>

						<?php elseif ( file_exists( $core_file ) ) : ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( ( is_dir( get_stylesheet_directory() . '/woocommerce/emails/' ) && is_writable( get_stylesheet_directory() . '/woocommerce/emails/' ) ) || is_writable( get_stylesheet_directory() ) ) : ?>
									<a href="<?php echo remove_query_arg( array( 'delete_template', 'saved' ), add_query_arg( 'move_template', $template ) ); ?>" class="button"><?php _e( 'Copy file to theme', 'woocommerce' ); ?></a>
								<?php endif; ?>

								<?php printf( __( 'To override and edit this email template copy <code>%s</code> to your theme folder: <code>%s</code>.', 'woocommerce' ), plugin_basename( $core_file ) , 'yourtheme/woocommerce/' . $this->$template ); ?>
							</p>

							<div class="editor" style="display:none">

								<textarea class="code" readonly="readonly" disabled="disabled" cols="25" rows="20"><?php echo file_get_contents( $core_file ); ?></textarea>

							</div>

						<?php else : ?>

							<p><?php _e( 'File was not found.', 'woocommerce' ); ?></p>

						<?php endif; ?>

					</div>
					<?php
				endforeach;
			?>
			</div>
			<?php
			$woocommerce->add_inline_js("
				jQuery('select.email_type').change(function(){

					var val = jQuery( this ).val();

					jQuery('.template_plain, .template_html').show();

					if ( val != 'multipart' && val != 'html' )
						jQuery('.template_html').hide();

					if ( val != 'multipart' && val != 'plain' )
						jQuery('.template_plain').hide();

				}).change();

				var view = '" . esc_js( __( 'View template', 'woocommerce' ) ) . "';
				var hide = '" . esc_js( __( 'Hide template', 'woocommerce' ) ) . "';

				jQuery('a.toggle_editor').text( view ).toggle( function() {
					jQuery( this ).text( hide ).closest('.template').find('.editor').slideToggle();
					return false;
				}, function() {
					jQuery( this ).text( view ).closest('.template').find('.editor').slideToggle();
					return false;
				} );

				jQuery('a.delete_template').click(function(){
					var answer = confirm('" . esc_js( __( 'Are you sure you want to delete this template file?', 'woocommerce' ) ) . "');

					if (answer)
						return true;

					return false;
				});

				jQuery('.editor textarea').change(function(){
					var name = jQuery(this).attr( 'data-name' );

					if ( name )
						jQuery(this).attr( 'name', name );
				});
			");
		}
	}
}
