<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'WC_Email', false ) ) {
	return;
}

/**
 * Email Class
 *
 * WooCommerce Email Class which is extended by specific email template classes to add emails to WooCommerce
 *
 * @class       WC_Email
 * @version     2.5.0
 * @package     WooCommerce/Classes/Emails
 * @author      WooThemes
 * @extends     WC_Settings_API
 */
class WC_Email extends WC_Settings_API {

	/**
	 * Email method ID.
	 * @var String
	 */
	public $id;

	/**
	 * Email method title.
	 * @var string
	 */
	public $title;

	/**
	 * 'yes' if the method is enabled.
	 * @var string yes, no
	 */
	public $enabled;

	/**
	 * Description for the email.
	 * @var string
	 */
	public $description;

	/**
	 * Plain text template path.
	 * @var string
	 */
	public $template_plain;

	/**
	 * HTML template path.
	 * @var string
	 */
	public $template_html;

	/**
	 * Template path.
	 * @var string
	 */
	public $template_base;

	/**
	 * Recipients for the email.
	 * @var string
	 */
	public $recipient;

	/**
	 * Heading for the email content.
	 * @var string
	 */
	public $heading;

	/**
	 * Subject for the email.
	 * @var string
	 */
	public $subject;

	/**
	 * Object this email is for, for example a customer, product, or email.
	 * @var object|bool
	 */
	public $object;

	/**
	 * Strings to find in subjects/headings.
	 * @var array
	 */
	public $find = array();

	/**
	 * Strings to replace in subjects/headings.
	 * @var array
	 */
	public $replace = array();

	/**
	 * Mime boundary (for multipart emails).
	 * @var string
	 */
	public $mime_boundary;

	/**
	 * Mime boundary header (for multipart emails).
	 * @var string
	 */
	public $mime_boundary_header;

	/**
	 * True when email is being sent.
	 * @var bool
	 */
	public $sending;

	/**
	 * True when the email notification is sent manually only.
	 * @var bool
	 */
	protected $manual = false;

	/**
	 * True when the email notification is sent to customers.
	 * @var bool
	 */
	protected $customer_email = false;

	/**
	 *  List of preg* regular expression patterns to search for,
	 *  used in conjunction with $plain_replace.
	 *  https://raw.github.com/ushahidi/wp-silcc/master/class.html2text.inc
	 *  @var array $plain_search
	 *  @see $plain_replace
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
		'/&[^&\s;]+;/i',                                 // Unknown/unhandled entities
		'/[ ]{2,}/',                                      // Runs of spaces, post-handling
	);

	/**
	 *  List of pattern replacements corresponding to patterns searched.
	 *  @var array $plain_replace
	 *  @see $plain_search
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
		' ',                                             // Runs of spaces, post-handling
	);

	/**
	 * Constructor.
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
		add_action( 'phpmailer_init', array( $this, 'handle_multipart' ) );
	}

	/**
	 * Handle multipart mail.
	 *
	 * @param PHPMailer $mailer
	 * @return PHPMailer
	 */
	public function handle_multipart( $mailer ) {
		if ( $this->sending && 'multipart' === $this->get_email_type() ) {
			$mailer->AltBody = wordwrap( preg_replace( $this->plain_search, $this->plain_replace, strip_tags( $this->get_content_plain() ) ) );
			$this->sending   = false;
		}
		return $mailer;
	}

	/**
	 * Format email string.
	 *
	 * @param mixed $string
	 * @return string
	 */
	public function format_string( $string ) {
		return str_replace( apply_filters( 'woocommerce_email_format_string_find', $this->find, $this ), apply_filters( 'woocommerce_email_format_string_replace', $this->replace, $this ), $string );
	}

	/**
	 * Get email subject.
	 *
	 * @return string
	 */
	public function get_subject() {
		return apply_filters( 'woocommerce_email_subject_' . $this->id, $this->format_string( $this->subject ), $this->object );
	}

	/**
	 * Get email heading.
	 *
	 * @return string
	 */
	public function get_heading() {
		return apply_filters( 'woocommerce_email_heading_' . $this->id, $this->format_string( $this->heading ), $this->object );
	}

	/**
	 * Get valid recipients.
	 * @return string
	 */
	public function get_recipient() {
		$recipient  = apply_filters( 'woocommerce_email_recipient_' . $this->id, $this->recipient, $this->object );
		$recipients = array_map( 'trim', explode( ',', $recipient ) );
		$recipients = array_filter( $recipients, 'is_email' );
		return implode( ', ', $recipients );
	}

	/**
	 * Get email headers.
	 *
	 * @return string
	 */
	public function get_headers() {
		$header = "Content-Type: " . $this->get_content_type() . "\r\n";

		if ( 'new_order' === $this->id ) {
			$header .= 'Reply-to: ' . $this->object->get_billing_first_name() . ' ' . $this->object->get_billing_last_name() . ' <' . $this->object->get_billing_email() . ">\r\n";
		}

		return apply_filters( 'woocommerce_email_headers', $header, $this->id, $this->object );
	}

	/**
	 * Get email attachments.
	 *
	 * @return string
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
	 * Get email content type.
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
	 * Return the email's title
	 * @return string
	 */
	public function get_title() {
		return apply_filters( 'woocommerce_email_title', $this->title, $this );
	}

	/**
	 * Return the email's description
	 * @return string
	 */
	public function get_description() {
		return apply_filters( 'woocommerce_email_description', $this->description, $this );
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
		return apply_filters( 'woocommerce_email_get_option', $value, $this, $value, $key, $empty_value );
	}

	/**
	 * Checks if this email is enabled and will be sent.
	 * @return bool
	 */
	public function is_enabled() {
		return apply_filters( 'woocommerce_email_enabled_' . $this->id, 'yes' === $this->enabled, $this->object );
	}

	/**
	 * Checks if this email is manually sent
	 * @return bool
	 */
	public function is_manual() {
		return $this->manual;
	}

	/**
	 * Checks if this email is customer focussed.
	 * @return bool
	 */
	public function is_customer_email() {
		return $this->customer_email;
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get email content.
	 *
	 * @return string
	 */
	public function get_content() {
		$this->sending = true;

		if ( 'plain' === $this->get_email_type() ) {
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
			ob_start();
			wc_get_template( 'emails/email-styles.php' );
			$css = apply_filters( 'woocommerce_email_styles', ob_get_clean() );

			// apply CSS styles inline for picky email clients
			try {
				$emogrifier = new Emogrifier( $content, $css );
				$content    = $emogrifier->emogrify();
			} catch ( Exception $e ) {
				$logger = wc_get_logger();
				$logger->error( $e->getMessage(), array( 'source' => 'emogrifier' ) );
			}
		}
		return $content;
	}

	/**
	 * Get the email content in plain text format.
	 * @return string
	 */
	public function get_content_plain() { return ''; }

	/**
	 * Get the email content in HTML format.
	 * @return string
	 */
	public function get_content_html() { return ''; }

	/**
	 * Get the from name for outgoing emails.
	 * @return string
	 */
	public function get_from_name() {
		$from_name = apply_filters( 'woocommerce_email_from_name', get_option( 'woocommerce_email_from_name' ), $this );
		return wp_specialchars_decode( esc_html( $from_name ), ENT_QUOTES );
	}

	/**
	 * Get the from address for outgoing emails.
	 * @return string
	 */
	public function get_from_address() {
		$from_address = apply_filters( 'woocommerce_email_from_address', get_option( 'woocommerce_email_from_address' ), $this );
		return sanitize_email( $from_address );
	}

	/**
	 * Send an email.
	 * @param string $to
	 * @param string $subject
	 * @param string $message
	 * @param string $headers
	 * @param string $attachments
	 * @return bool success
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
		$this->form_fields    = array(
			'enabled'         => array(
				'title'       => __( 'Enable/Disable', 'woocommerce' ),
				'type'        => 'checkbox',
				'label'       => __( 'Enable this email notification', 'woocommerce' ),
				'default'     => 'yes',
			),
			'subject'         => array(
				'title'       => __( 'Email subject', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default subject */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->subject . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
			'heading'         => array(
				'title'       => __( 'Email heading', 'woocommerce' ),
				'type'        => 'text',
				/* translators: %s: default heading */
				'description' => sprintf( __( 'Defaults to %s', 'woocommerce' ), '<code>' . $this->heading . '</code>' ),
				'placeholder' => '',
				'default'     => '',
				'desc_tip'    => true,
			),
			'email_type'      => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'     => 'html',
				'class'       => 'email_type wc-enhanced-select',
				'options'     => $this->get_email_type_options(),
				'desc_tip'    => true,
			),
		);
	}

	/**
	 * Email type options.
	 * @return array
	 */
	public function get_email_type_options() {
		$types = array( 'plain' => __( 'Plain text', 'woocommerce' ) );

		if ( class_exists( 'DOMDocument' ) ) {
			$types['html']      = __( 'HTML', 'woocommerce' );
			$types['multipart'] = __( 'Multipart', 'woocommerce' );
		}

		return $types;
	}

	/**
	 * Admin Panel Options Processing.
	 */
	public function process_admin_options() {
		// Save regular options
		parent::process_admin_options();

		$post_data = $this->get_post_data();

		// Save templates
		if ( isset( $post_data['template_html_code'] ) ) {
			$this->save_template( $post_data['template_html_code'], $this->template_html );
		}
		if ( isset( $post_data['template_plain_code'] ) ) {
			$this->save_template( $post_data['template_plain_code'], $this->template_plain );
		}
	}

	/**
	 * Get template.
	 *
	 * @param  string $type
	 * @return string
	 */
	public function get_template( $type ) {
		$type = basename( $type );

		if ( 'template_html' === $type ) {
			return $this->template_html;
		} elseif ( 'template_plain' === $type ) {
			return $this->template_plain;
		}
		return '';
	}

	/**
	 * Save the email templates.
	 *
	 * @since 2.4.0
	 * @param string $template_code
	 * @param string $template_path
	 */
	protected function save_template( $template_code, $template_path ) {
		if ( current_user_can( 'edit_themes' ) && ! empty( $template_code ) && ! empty( $template_path ) ) {
			$saved  = false;
			$file   = get_stylesheet_directory() . '/woocommerce/' . $template_path;
			$code   = wp_unslash( $template_code );

			if ( is_writeable( $file ) ) {
				$f = fopen( $file, 'w+' );

				if ( false !== $f ) {
					fwrite( $f, $code );
					fclose( $f );
					$saved = true;
				}
			}

			if ( ! $saved ) {
				$redirect = add_query_arg( 'wc_error', urlencode( __( 'Could not write to template file.', 'woocommerce' ) ) );
				wp_safe_redirect( $redirect );
				exit;
			}
		}
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
					$template_file = apply_filters( 'woocommerce_locate_core_template', $core_file, $template, $this->template_base, $this->id );

					// Copy template file
					copy( $template_file, $theme_file );

					/**
					 * woocommerce_copy_email_template action hook.
					 *
					 * @param string $template_type The copied template type
					 * @param string $email The email object
					 */
					do_action( 'woocommerce_copy_email_template', $template_type, $this );

					echo '<div class="updated"><p>' . __( 'Template file copied to theme.', 'woocommerce' ) . '</p></div>';
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
					 * woocommerce_delete_email_template action hook.
					 *
					 * @param string $template The deleted template type
					 * @param string $email The email object
					 */
					do_action( 'woocommerce_delete_email_template', $template_type, $this );

					echo '<div class="updated"><p>' . __( 'Template file deleted from theme.', 'woocommerce' ) . '</p></div>';
				}
			}
		}
	}

	/**
	 * Admin actions.
	 */
	protected function admin_actions() {
		// Handle any actions
		if (
			( ! empty( $this->template_html ) || ! empty( $this->template_plain ) )
			&& ( ! empty( $_GET['move_template'] ) || ! empty( $_GET['delete_template'] ) )
			&& 'GET' === $_SERVER['REQUEST_METHOD']
		) {
			if ( empty( $_GET['_wc_email_nonce'] ) || ! wp_verify_nonce( $_GET['_wc_email_nonce'], 'woocommerce_email_template_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'woocommerce' ) );
			}

			if ( ! current_user_can( 'edit_themes' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce' ) );
			}

			if ( ! empty( $_GET['move_template'] ) ) {
				$this->move_template_action( $_GET['move_template'] );
			}

			if ( ! empty( $_GET['delete_template'] ) ) {
				$this->delete_template_action( $_GET['delete_template'] );
			}
		}
	}

	/**
	 * Admin Options.
	 *
	 * Setup the email settings screen.
	 * Override this in your email.
	 *
	 * @since 1.0.0
	 */
	public function admin_options() {
		// Do admin actions.
		$this->admin_actions();
		?>
		<h2><?php echo esc_html( $this->get_title() ); ?> <?php wc_back_link( __( 'Return to emails', 'woocommerce' ), admin_url( 'admin.php?page=wc-settings&tab=email' ) ); ?></h2>

		<?php echo wpautop( wp_kses_post( $this->get_description() ) ); ?>

		<?php
			/**
			 * woocommerce_email_settings_before action hook.
			 * @param string $email The email object
			 */
			do_action( 'woocommerce_email_settings_before', $this );
		?>

		<table class="form-table">
			<?php $this->generate_settings_html(); ?>
		</table>

		<?php
			/**
			 * woocommerce_email_settings_after action hook.
			 * @param string $email The email object
			 */
			do_action( 'woocommerce_email_settings_after', $this );
		?>

		<?php if ( current_user_can( 'edit_themes' ) && ( ! empty( $this->template_html ) || ! empty( $this->template_plain ) ) ) { ?>
			<div id="template">
			<?php
				$templates = array(
					'template_html'  => __( 'HTML template', 'woocommerce' ),
					'template_plain' => __( 'Plain text template', 'woocommerce' ),
				);

				foreach ( $templates as $template_type => $title ) :
					$template = $this->get_template( $template_type );

					if ( empty( $template ) ) {
						continue;
					}

					$local_file    = $this->get_theme_template_file( $template );
					$core_file     = $this->template_base . $template;
					$template_file = apply_filters( 'woocommerce_locate_core_template', $core_file, $template, $this->template_base, $this->id );
					$template_dir  = apply_filters( 'woocommerce_template_directory', 'woocommerce', $template );
					?>
					<div class="template <?php echo $template_type; ?>">

						<h4><?php echo wp_kses_post( $title ); ?></h4>

						<?php if ( file_exists( $local_file ) ) { ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( is_writable( $local_file ) ) : ?>
									<a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'move_template', 'saved' ), add_query_arg( 'delete_template', $template_type ) ), 'woocommerce_email_template_nonce', '_wc_email_nonce' ) ); ?>" class="delete_template button"><?php _e( 'Delete template file', 'woocommerce' ); ?></a>
								<?php endif; ?>

								<?php printf( __( 'This template has been overridden by your theme and can be found in: %s.', 'woocommerce' ), '<code>' . trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template . '</code>' ); ?>
							</p>

							<div class="editor" style="display:none">
								<textarea class="code" cols="25" rows="20" <?php if ( ! is_writable( $local_file ) ) : ?>readonly="readonly" disabled="disabled"<?php else : ?>data-name="<?php echo $template_type . '_code'; ?>"<?php endif; ?>><?php echo file_get_contents( $local_file ); ?></textarea>
							</div>

						<?php } elseif ( file_exists( $template_file ) ) { ?>

							<p>
								<a href="#" class="button toggle_editor"></a>

								<?php if ( ( is_dir( get_stylesheet_directory() . '/' . $template_dir . '/emails/' ) && is_writable( get_stylesheet_directory() . '/' . $template_dir . '/emails/' ) ) || is_writable( get_stylesheet_directory() ) ) { ?>
									<a href="<?php echo esc_url( wp_nonce_url( remove_query_arg( array( 'delete_template', 'saved' ), add_query_arg( 'move_template', $template_type ) ), 'woocommerce_email_template_nonce', '_wc_email_nonce' ) ); ?>" class="button"><?php _e( 'Copy file to theme', 'woocommerce' ); ?></a>
								<?php } ?>

								<?php printf( __( 'To override and edit this email template copy %1$s to your theme folder: %2$s.', 'woocommerce' ), '<code>' . plugin_basename( $template_file ) . '</code>', '<code>' . trailingslashit( basename( get_stylesheet_directory() ) ) . $template_dir . '/' . $template . '</code>' ); ?>
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
				jQuery( 'select.email_type' ).change( function() {

					var val = jQuery( this ).val();

					jQuery( '.template_plain, .template_html' ).show();

					if ( val != 'multipart' && val != 'html' ) {
						jQuery('.template_html').hide();
					}

					if ( val != 'multipart' && val != 'plain' ) {
						jQuery('.template_plain').hide();
					}

				}).change();

				var view = '" . esc_js( __( 'View template', 'woocommerce' ) ) . "';
				var hide = '" . esc_js( __( 'Hide template', 'woocommerce' ) ) . "';

				jQuery( 'a.toggle_editor' ).text( view ).toggle( function() {
					jQuery( this ).text( hide ).closest(' .template' ).find( '.editor' ).slideToggle();
					return false;
				}, function() {
					jQuery( this ).text( view ).closest( '.template' ).find( '.editor' ).slideToggle();
					return false;
				} );

				jQuery( 'a.delete_template' ).click( function() {
					if ( window.confirm('" . esc_js( __( 'Are you sure you want to delete this template file?', 'woocommerce' ) ) . "') ) {
						return true;
					}

					return false;
				});

				jQuery( '.editor textarea' ).change( function() {
					var name = jQuery( this ).attr( 'data-name' );

					if ( name ) {
						jQuery( this ).attr( 'name', name );
					}
				});
			" );
		}
	}
}
