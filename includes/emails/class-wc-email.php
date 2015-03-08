<?php
/**
 * Email Class
 *
 * WooCommerce Email Class which is extended by specific email template classes to add emails to WooCommerce
 *
 * @class       WC_Email
 * @version     2.0.0
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
 * @extends     WC_Settings_API
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Email' ) ) {
	return;
}

/**
 * WC_Email
 */
class WC_Email extends WC_Settings_API {

	/**
	 * Email method ID.
	 *
	 * @var String
	 */
	public $id;

	/**
	 * Email method title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * 'yes' if the method is enabled.
	 *
	 * @var string
	 */
	public $enabled;

	/**
	 * Description for the email.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * Plain text template path.
	 *
	 * @var string
	 */
	public $template_plain;

	/**
	 * HTML template path.
	 *
	 * @var string
	 */
	public $template_html;

	/**
	 * Template path.
	 *
	 * @var string
	 */
	public $template_base;

	/**
	 * Recipients for the email.
	 *
	 * @var string
	 */
	public $recipient;

	/**
	 * Heading for the email content.
	 *
	 * @var string
	 */
	public $heading;

	/**
	 * Subject for the email.
	 *
	 * @var string
	 */
	public $subject;

	/**
	 * Object this email is for, for example a customer, product, or email.
	 *
	 * @var object
	 */
	public $object;

	/**
	 * Strings to find in subjects/headings.
	 *
	 * @var array
	 */
	public $find;

	/**
	 * Strings to replace in subjects/headings.
	 *
	 * @var array
	 */
	public $replace;

	/**
	 * Mime boundary (for multipart emails).
	 *
	 * @var string
	 */
	public $mime_boundary;

	/**
	 * Mime boundary header (for multipart emails).
	 *
	 * @var string
	 */
	public $mime_boundary_header;

	/**
	 * True when email is being sent.
	 *
	 * @var bool
	 */
	public $sending;

	/**
	 *  List of preg* regular expression patterns to search for,
	 *  used in conjunction with $replace.
	 *  https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
	 *
	 *  @var array $search
	 *  @see $replace
	 */
	public $plain_search = array(
		"/\r/",                                          // Non-legal carriage return
		'/&(nbsp|#160);/i',                              // Non-breaking space
		'/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i', // Double quotes
		'/&(apos|rsquo|lsquo|#8216|#8217);/i',           // Single quotes
		'/&gt;/i',                                       // Greater-than
		'/&lt;/i',                                       // Less-than
		'/&#38;/i',                                      // Ampersand
		'/&#038;/i',                                     // Ampersand
		'/&amp;/i',                                      // Ampersand
		'/&(copy|#169);/i',                              // Copyright
		'/&(trade|#8482|#153);/i',                       // Trademark
		'/&(reg|#174);/i',                               // Registered
		'/&(mdash|#151|#8212);/i',                       // mdash
		'/&(ndash|minus|#8211|#8722);/i',                // ndash
		'/&(bull|#149|#8226);/i',                        // Bullet
		'/&(pound|#163);/i',                             // Pound sign
		'/&(euro|#8364);/i',                             // Euro sign
		'/&#36;/',                                       // Dollar sign
		'/&[^&;]+;/i',                                   // Unknown/unhandled entities
		'/[ ]{2,}/'                                      // Runs of spaces, post-handling
	);

	/**
	 *  List of pattern replacements corresponding to patterns searched.
	 *
	 *  @var array $replace
	 *  @see $search
	 */
	public $plain_replace = array(
		'',                                             // Non-legal carriage return
		' ',                                            // Non-breaking space
		'"',                                            // Double quotes
		"'",                                            // Single quotes
		'>',                                            // Greater-than
		'<',                                            // Less-than
		'&',                                            // Ampersand
		'&',                                            // Ampersand
		'&',                                            // Ampersand
		'(c)',                                          // Copyright
		'(tm)',                                         // Trademark
		'(R)',                                          // Registered
		'--',                                           // mdash
		'-',                                            // ndash
		'*',                                            // Bullet
		'£',                                            // Pound sign
		'EUR',                                          // Euro sign. € ?
		'$',                                            // Dollar sign
		'',                                             // Unknown/unhandled entities
		' '                                             // Runs of spaces, post-handling
	);

	/**
	 * Constructor
	 */
	public function __construct() {

		// Init settings
		$this->init_form_fields();
		$this->init_settings();

		// Save settings hook
		add_action( 'woocommerce_update_options_email_' . $this->id, array( $this, 'process_admin_options' ) );

		// Default template base if not declared in child constructor
		if ( is_null( $this->template_base ) ) {
			$this->template_base = WC()->plugin_path() . '/templates/';
		}

		// Settings
		$this->heading     = $this->get_option( 'heading', $this->heading );
		$this->subject     = $this->get_option( 'subject', $this->subject );
		$this->email_type  = $this->get_option( 'email_type' );
		$this->enabled     = $this->get_option( 'enabled' );

		// Find/replace
		$this->find['blogname']      = '{blogname}';
		$this->find['site-title']    = '{site_title}';

		$this->replace['blogname']   = $this->get_blogname();
		$this->replace['site-title'] = $this->get_blogname();

		// For multipart messages
		add_filter( 'phpmailer_init', array( $this, 'handle_multipart' ) );
	}

	/**
	 * handle_multipart function.
	 *
	 * @param PHPMailer $mailer
	 * @return PHPMailer
	 */
	public function handle_multipart( $mailer )  {

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
	 * @param mixed $string
	 * @return string
	 */
	public function format_string( $string ) {
		return str_replace( $this->find, $this->replace, $string );
	}

	/**
	 * get_subject function.
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * get_heading function.
	 *
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	/**
	 * get_recipient function.
	 *
	 * @return string
	 */
	public function get_recipient() {
		return apply_filters( 'woocommerce_email_recipient_' . $this->id, $this->recipient, $this->object );
	}

	/**
	 * get_headers function.
	 *
	 * @return string
	 */
	public function get_headers() {
		return apply_filters( 'woocommerce_email_headers', "Content-Type: " . $this->get_content_type() . "\r\n", $this->id, $this->object );
	}

	/**
	 * get_attachments function.
	 *
	 * @return string|array
	 */
	public function get_attachments() {
		return apply_filters( 'woocommerce_email_attachments', array(), $this->id, $this->object );
	}

	/**
	 * get_type function.
	 *
	 * @return string
	 */
	public function get_email_type() {
		return $this->email_type && class_exists( 'DOMDocument' ) ? $this->email_type : 'plain';
	}

	/**
	 * get_content_type function.
	 *
	 * @return string
	 */
	public function get_content_type() {
		switch ( $this->get_email_type() ) {
			case 'html' :
				return 'text/html';
			case 'multipart' :
				return 'multipart/alternative';
			default :
				return 'text/plain';
		}
	}

	/**
	 * Proxy to parent's get_option and attempt to localize the result using gettext.
	 *
	 * @param string $key
	 * @param mixed  $empty_value
	 * @return string
	 */
	public function get_option( $key, $empty_value = null ) {
		$value = parent::get_option( $key, $empty_value );

		return apply_filters( 'woocommerce_email_get_option', __( $value ), $this, $value, $key, $empty_value );
	}

	/**
	 * Checks if this email is enabled and will be sent.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$enabled = $this->enabled == 'yes' ? true : false;

		return apply_filters( 'woocommerce_email_enabled_' . $this->id, $enabled, $this->object );
	}

	/**
	 * get_blogname function.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * get_content function.
	 *
	 * @return string
	 */
	public function get_content() {

		$this->sending = true;

		if ( $this->get_email_type() == 'plain' ) {
			$email_content = preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) );
		} else {
			$email_content = $this->get_content_html();
		}

		return wordwrap( $email_content, 70 );
	}

	/**
	 * Apply inline styles to dynamic content.
	 *
	 * @param string|null $content
	 * @return string
	 */
	public function style_inline( $content ) {
		// make sure we only inline CSS for html emails
		if ( in_array( $this->get_content_type(), array( 'text/html', 'multipart/alternative' ) ) && class_exists( 'DOMDocument' ) ) {

			// get CSS styles
			ob_start();
			wc_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'woocommerce_email_styles', ob_get_clean() );

			try {

				// apply CSS styles inline for picky email clients
				$emogrifier = new Emogrifier( $content, $css );
				$content = $emogrifier->emogrify();

			} catch ( Exception $e ) {

				$logger = new WC_Logger();

				$logger->add( 'emogrifier', $e->getMessage() );
			}
		}

		return $content;
	}

	/**
	 * get_content_plain function.
	 *
	 * @return string
	 */
	public function get_content_plain() {}

	/**
	 * get_content_html function.
	 *
	 * @return string
	 */
	public function get_content_html() {}

	/**
	 * Get from name for email.
	 *
	 * @return string
	 */
	public function get_from_name() {
		return wp_specialchars_decode( esc_html( get_option( 'woocommerce_email_from_name' ) ), ENT_QUOTES );
	}

	/**
	 * Get from email address.
	 *
	 * @return string
	 */
	public function get_from_address() {
		return sanitize_email( get_option( 'woocommerce_email_from_address' ) );
	}

	/**
	 * Send the email.
	 *
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $headers
	 * @param string $attachments
	 * @return bool
	 */
	public function send( $to, $subject, $message, $headers, $attachments ) {

		add_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		add_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		$message = apply_filters( 'woocommerce_mail_content', $this->style_inline( $message ) );
		$return  = wp_mail( $to, $subject, $message, $headers, $attachments );

		remove_filter( 'wp_mail_from', array( $this, 'get_from_address' ) );
		remove_filter( 'wp_mail_from_name', array( $this, 'get_from_name' ) );
		remove_filter( 'wp_mail_content_type', array( $this, 'get_content_type' ) );

		return $return;
	}

	/**
	 * Initialise Settings Form Fields - these are generic email options most will use.
	 */
	public function init_form_fields() {
		$this->form_fields = array(
			'enabled' => array(
				'title'         => __( 'Enable/Disable', 'woocommerce' ),
				'type'          => 'checkbox',
				'label'         => __( 'Enable this email notification', 'woocommerce' ),
				'default'       => 'yes'
			),
			'subject' => array(
				'title'         => __( 'Email subject', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->subject ),
				'placeholder'   => '',
				'default'       => ''
			),
			'heading' => array(
				'title'         => __( 'Email heading', 'woocommerce' ),
				'type'          => 'text',
				'description'   => sprintf( __( 'Defaults to <code>%s</code>', 'woocommerce' ), $this->heading ),
				'placeholder'   => '',
				'default'       => ''
			),
			'email_type' => array(
				'title'         => __( 'Email type', 'woocommerce' ),
				'type'          => 'select',
				'description'   => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'       => 'html',
				'class'         => 'email_type wc-enhanced-select',
				'options'       => $this->get_email_type_options()
			)
		);
	}

	/**
	 * Email type options
	 *
	 * @return array
	 */
	public function get_email_type_options() {
		$types = array(
			'plain' => __( 'Plain text', 'woocommerce' )
		);

		if ( class_exists( 'DOMDocument' ) ) {
			$types['html'] = __( 'HTML', 'woocommerce' );
			$types['multipart'] = __( 'Multipart', 'woocommerce' );
		}

		return $types;
	}

	/**
	 * Admin Panel Options Processing
	 * - Saves the options to the DB
	 *
	 * @since 1.0.0
	 * @return boolean|null
	 */
	public function process_admin_options() {

		// Save regular options
		parent::process_admin_options();

		// Save templates
		if ( ! empty( $_POST['template_html_code'] ) && ! empty( $this->template_html ) ) {

			$saved  = false;
			$file   = get_stylesheet_directory() . '/woocommerce/' . $this->template_html;
			$code   = stripslashes( $_POST['template_html_code'] );

			if ( is_writeable( $file ) ) {

				$f = fopen( $file, 'w+' );

				if ( $f !== false ) {
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

			$saved  = false;
			$file   = get_stylesheet_directory() . '/woocommerce/' . $this->template_plain;
			$code   = stripslashes( $_POST['template_plain_code'] );

			if ( is_writeable( $file ) ) {

				$f = fopen( $file, 'w+' );

				if ( $f !== false ) {
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
	 * Get template.
	 *
	 * @param  string $type
	 *
	 * @return string
	 */
	public function get_template( $type ) {
		$type = esc_attr( basename( $type ) );

		if ( 'template_html' == $type ) {
			return $this->template_html;
		} else if ( 'template_plain' == $type ) {
			return $this->template_plain;
		}

		return '';
	}

	/**
	 * Get the template file in the current theme.
	 *
	 * @param  string $template
	 *
	 * @return string
	 */
	public function get_theme_template_file( $template ) {
		return get_stylesheet_directory() . '/' . apply_filters( 'woocommerce_template_directory', 'woocommerce', $template ) . '/' . $template;
	}

	/**
	 * Move template action.
	 *
	 * @param string $template_type
	 */
	protected function move_template_action( $template_type ) {
		if ( $template = $this->get_template( $template_type ) ) {

			if ( ! empty( $template ) ) {

				$theme_file = $this->get_theme_template_file( $template );

				if ( wp_mkdir_p( dirname( $theme_file ) ) && ! file_exists( $theme_file ) ) {

					// Locate template file
					$core_file     = $this->template_base . $template;
					$template_file = apply_filters( 'woocommerce_locate_core_template', $core_file, $template, $this->template_base );

					// Copy template file
					copy( $template_file, $theme_file );

					/**
					 * woocommerce_copy_email_template action hook
					 *
					 * @param string $template_type The copied template type
					 * @param string $email The email object
					 */
					do_action( 'woocommerce_copy_email_template', $template_type, $this );

					echo '<div class="updated fade"><p>' . __( 'Template file copied to theme.', 'woocommerce' ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * Delete template action.
	 *
	 * @param string $template_type
	 */
	protected function delete_template_action( $template_type ) {
		if ( $template = $this->get_template( $template_type ) ) {

			if ( ! empty( $template ) ) {

				$theme_file = $this->get_theme_template_file( $template );

				if ( file_exists( $theme_file ) ) {
					unlink( $theme_file );

					/**
					 * woocommerce_delete_email_template action hook
					 *
					 * @param string $template The deleted template type
					 * @param string $email The email object
					 */
					do_action( 'woocommerce_delete_email_template', $template_type, $this );

					echo '<div class="updated fade"><p>' . __( 'Template file deleted from theme.', 'woocommerce' ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * Admin actions.
	 */
	protected function admin_actions() {
		// Handle any actions
		if ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) {

			if ( ! empty( $_GET['move_template'] ) ) {
				$this->move_template_action( $_GET['move_template'] );
			}

			if ( ! empty( $_GET['delete_template'] ) ) {
				$this->delete_template_action( $_GET['delete_template'] );
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
	 */
	public function admin_options() {
		// Do admin acations.
		$this->admin_actions();

		?>
		<h3><?php echo ( ! empty( $this->title ) ) ? $this->title : __( 'Settings','woocommerce' ) ; ?></h3>

		<?php echo ( ! empty( $this->description ) ) ? wpautop( $this->description ) : ''; ?>

		<?php
			/**
			 * woocommerce_email_settings_before action hook
			 *
			 * @param string $email The email object
			 */
			do_action( 'woocommerce_email_settings_before', $this );
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>

		<?php
			/**
			 * woocommerce_email_settings_after action hook
			 *
			 * @param string $email The email object
			 */
			do_action( 'woocommerce_email_settings_after', $this );
		?>

		<?php if ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) { ?>
			<div id="template">
			<?php
				$templates = array(
					'template_html'  => __( 'HTML template', 'woocommerce' ),
					'template_plain' => __( 'Plain text template', 'woocommerce' )
				);

				foreach ( $templates as $template_type => $title ) :
					$template = $this->get_template( $template_type );

					if ( empty( $template ) ) {
						continue;
					}

					$local_file    = $this->get_theme_template_file( $template );
					$core_file     = $this->template_base . $template;
					$template_file = apply_filters( 'woocommerce_locate_core_template', $core_file, $template, $this->template_base );
					$template_dir  = apply_filters( 'woocommerce_template_directory', 'woocommerce', $template );
					?>
					<div class="template <?php echo $template_type; ?>">

						<h4><?php echo wp_kses_post( $title ); ?></h4>

						<?php if ( file_exists( $local_file ) ) { ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( is_writable( $local_file ) ) : ?>
									<a href="<?php echo esc_url( remove_query_arg( array( 'move_template', 'saved' ), add_query_arg( 'delete_template', $template_type ) ) ); ?>" class="delete_template button"><?php _e( 'Delete template file', 'woocommerce' ); ?></a>
								<?php endif; ?>

								<?php printf( __( 'This template has been overridden by your theme and can be found in: <code>%s</code>.', 'woocommerce' ), 'yourtheme/' . $template_dir . '/' . $template ); ?>
							</p>

							<div class="editor" style="display:none">
								<textarea class="code" cols="25" rows="20" <?php if ( ! is_writable( $local_file ) ) : ?>readonly="readonly" disabled="disabled"<?php else : ?>data-name="<?php echo $template_type . '_code'; ?>"<?php endif; ?>><?php echo file_get_contents( $local_file ); ?></textarea>
							</div>

						<?php } elseif ( file_exists( $template_file ) ) { ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( ( is_dir( get_stylesheet_directory() . '/' . $template_dir . '/emails/' ) && is_writable( get_stylesheet_directory() . '/' . $template_dir . '/emails/' ) ) || is_writable( get_stylesheet_directory() ) ) { ?>
									<a href="<?php echo esc_url( remove_query_arg( array( 'delete_template', 'saved' ), add_query_arg( 'move_template', $template_type ) ) ); ?>" class="button"><?php _e( 'Copy file to theme', 'woocommerce' ); ?></a>
								<?php } ?>

								<?php printf( __( 'To override and edit this email template copy <code>%s</code> to your theme folder: <code>%s</code>.', 'woocommerce' ), plugin_basename( $template_file ) , 'yourtheme/' . $template_dir . '/' . $template ); ?>
							</p>

							<div class="editor" style="display:none">
								<textarea class="code" readonly="readonly" disabled="disabled" cols="25" rows="20"><?php echo file_get_contents( $template_file ); ?></textarea>
							</div>

						<?php } else { ?>

							<p><?php _e( 'File was not found.', 'woocommerce' ); ?></p>

						<?php } ?>

					</div>
					<?php
				endforeach;
			?>
			</div>
			<?php
			wc_enqueue_js( "
				jQuery('select.email_type').change(function() {

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
			" );
		}
	}
}
