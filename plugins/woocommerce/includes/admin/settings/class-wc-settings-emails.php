<?php
/**
 * WooCommerce Email Settings
 *
 * @package WooCommerce\Admin
 * @version 2.1.0
 */

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\Email\EmailColors;
use Automattic\WooCommerce\Internal\Email\EmailFont;
use Automattic\WooCommerce\Internal\Email\EmailStyleSync;
use Automattic\WooCommerce\Internal\Features\FeaturesController;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WC_Settings_Emails', false ) ) {
	return new WC_Settings_Emails();
}

/**
 * WC_Settings_Emails.
 */
class WC_Settings_Emails extends WC_Settings_Page {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->id    = 'email';
		$this->label = __( 'Emails', 'woocommerce' );

		add_action( 'woocommerce_admin_field_email_notification', array( $this, 'email_notification_setting' ) );
		add_action( 'woocommerce_admin_field_email_preview', array( $this, 'email_preview' ) );
		add_action( 'woocommerce_admin_field_email_image_url', array( $this, 'email_image_url' ) );
		add_action( 'woocommerce_admin_field_email_font_family', array( $this, 'email_font_family' ) );
		add_action( 'woocommerce_admin_field_email_color_palette', array( $this, 'email_color_palette' ) );
		add_action( 'woocommerce_email_settings_after', array( $this, 'email_preview_single' ) );
		if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
			add_filter( 'woocommerce_admin_settings_sanitize_option_woocommerce_email_header_image', array( $this, 'sanitize_email_header_image' ), 10, 3 );
		}
		add_filter( 'woocommerce_tracks_event_properties', array( $this, 'append_feature_email_improvements_to_tracks' ) );
		add_action( FeaturesController::FEATURE_ENABLED_CHANGED_ACTION, array( $this, 'track_email_improvements_feature_change' ), 10, 2 );
		parent::__construct();
	}

	/**
	 * Setting page icon.
	 *
	 * @var string
	 */
	public $icon = 'atSymbol';

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			'' => __( 'Email options', 'woocommerce' ),
		);
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {
		$desc_help_text = sprintf(
		/* translators: %1$s: Link to WP Mail Logging plugin, %2$s: Link to Email FAQ support page. */
			__( 'To ensure your store&rsquo;s notifications arrive in your and your customers&rsquo; inboxes, we recommend connecting your email address to your domain and setting up a dedicated SMTP server. If something doesn&rsquo;t seem to be sending correctly, install the <a href="%1$s">WP Mail Logging Plugin</a> or check the <a href="%2$s">Email FAQ page</a>.', 'woocommerce' ),
			'https://wordpress.org/plugins/wp-mail-logging/',
			'https://woocommerce.com/document/email-faq'
		);

		/* translators: %s: Nonced email preview link */
		$email_template_description = sprintf( __( 'This section lets you customize the WooCommerce emails. <a href="%s" target="_blank">Click here to preview your email template</a>.', 'woocommerce' ), wp_nonce_url( admin_url( '?preview_woocommerce_mail=true' ), 'preview-mail' ) );
		$logo_image                 = array(
			'title'       => __( 'Header image', 'woocommerce' ),
			'desc'        => __( 'Paste the URL of an image you want to show in the email header. Upload images using the media uploader (Media > Add New).', 'woocommerce' ),
			'id'          => 'woocommerce_email_header_image',
			'type'        => 'text',
			'css'         => 'min-width:400px;',
			'placeholder' => __( 'N/A', 'woocommerce' ),
			'default'     => '',
			'autoload'    => false,
			'desc_tip'    => true,
		);
		$logo_image_width           = null;
		$header_alignment           = null;
		$font_family                = null;

		/* translators: %s: Available placeholders for use */
		$footer_text_description = __( 'The text to appear in the footer of all WooCommerce emails.', 'woocommerce' ) . ' ' . sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '{site_title} {site_url}' );
		$footer_text_default     = '{site_title} &mdash; Built with {WooCommerce}';

		// These defaults should be chosen by the same logic as the other color option properties.
		list(
			'base_color_default' => $base_color_default,
			'bg_color_default' => $bg_color_default,
			'body_bg_color_default' => $body_bg_color_default,
			'body_text_color_default' => $body_text_color_default,
			'footer_text_color_default' => $footer_text_color_default,
		) = EmailColors::get_default_colors();

		$base_color_title = __( 'Base color', 'woocommerce' );
		/* translators: %s: default color */
		$base_color_desc = sprintf( __( 'The base color for WooCommerce email templates. Default %s.', 'woocommerce' ), '<code>' . $base_color_default . '</code>' );

		$bg_color_title = __( 'Background color', 'woocommerce' );
		/* translators: %s: default color */
		$bg_color_desc = sprintf( __( 'The background color for WooCommerce email templates. Default %s.', 'woocommerce' ), '<code>' . $bg_color_default . '</code>' );

		$body_bg_color_title = __( 'Body background color', 'woocommerce' );
		/* translators: %s: default color */
		$body_bg_color_desc = sprintf( __( 'The main body background color. Default %s.', 'woocommerce' ), '<code>' . $body_bg_color_default . '</code>' );

		$body_text_color_title = __( 'Body text color', 'woocommerce' );
		/* translators: %s: default color */
		$body_text_color_desc = sprintf( __( 'The main body text color. Default %s.', 'woocommerce' ), '<code>' . $body_text_color_default . '</code>' );

		$footer_text_color_title = __( 'Footer text color', 'woocommerce' );
		/* translators: %s: footer default color */
		$footer_text_color_desc = sprintf( __( 'The footer text color. Default %s.', 'woocommerce' ), '<code>' . $footer_text_color_default . '</code>' );

		$color_palette_section_header = null;
		$color_palette_section_end    = null;

		if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
			$email_template_description = __( 'Customize your WooCommerce email template and preview it below.', 'woocommerce' );
			$logo_image                 = array(
				'title'       => __( 'Logo', 'woocommerce' ),
				'desc'        => __( 'Add your logo to each of your WooCommerce emails. If no logo is uploaded, your site title will be used instead.', 'woocommerce' ),
				'id'          => 'woocommerce_email_header_image',
				'type'        => 'email_image_url',
				'css'         => 'min-width:400px;',
				'placeholder' => __( 'N/A', 'woocommerce' ),
				'default'     => '',
				'autoload'    => false,
				'desc_tip'    => true,
			);
			$logo_image_width           = array(
				'title'    => __( 'Logo width (px)', 'woocommerce' ),
				'id'       => 'woocommerce_email_header_image_width',
				'desc_tip' => '',
				'default'  => 120,
				'type'     => 'number',
			);
			$header_alignment           = array(
				'title'    => __( 'Header alignment', 'woocommerce' ),
				'id'       => 'woocommerce_email_header_alignment',
				'desc_tip' => '',
				'default'  => 'left',
				'type'     => 'select',
				'class'    => 'wc-enhanced-select',
				'options'  => array(
					'left'   => __( 'Left', 'woocommerce' ),
					'center' => __( 'Center', 'woocommerce' ),
					'right'  => __( 'Right', 'woocommerce' ),
				),
			);

			$font_family = array(
				'title'   => __( 'Font family', 'woocommerce' ),
				'id'      => 'woocommerce_email_font_family',
				'default' => 'Helvetica',
				'type'    => 'email_font_family',
			);

			/* translators: %s: Available placeholders for use */
			$footer_text_description = __( 'This text will appear in the footer of all of your WooCommerce emails.', 'woocommerce' ) . ' ' . sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '{site_title} {site_url} {store_address} {store_email}' );
			$footer_text_default     = '{site_title}<br />{store_address}';

			$base_color_title = __( 'Accent', 'woocommerce' );
			/* translators: %s: default color */
			$base_color_desc = sprintf( __( 'Customize the color of your buttons and links. Default %s.', 'woocommerce' ), '<code>' . $base_color_default . '</code>' );

			$bg_color_title = __( 'Email background', 'woocommerce' );
			/* translators: %s: default color */
			$bg_color_desc = sprintf( __( 'Select a color for the background of your emails. Default %s.', 'woocommerce' ), '<code>' . $bg_color_default . '</code>' );

			$body_bg_color_title = __( 'Content background', 'woocommerce' );
			/* translators: %s: default color */
			$body_bg_color_desc = sprintf( __( 'Choose a background color for the content area of your emails. Default %s.', 'woocommerce' ), '<code>' . $body_bg_color_default . '</code>' );

			$body_text_color_title = __( 'Heading & text', 'woocommerce' );
			/* translators: %s: default color */
			$body_text_color_desc = sprintf( __( 'Set the color of your headings and text. Default %s.', 'woocommerce' ), '<code>' . $body_text_color_default . '</code>' );

			$footer_text_color_title = __( 'Secondary text', 'woocommerce' );
			/* translators: %s: footer default color */
			$footer_text_color_desc = sprintf( __( 'Choose a color for your secondary text, such as your footer content. Default %s.', 'woocommerce' ), '<code>' . $footer_text_color_default . '</code>' );

			$color_palette_section_header = array(
				'title' => __( 'Color palette', 'woocommerce' ),
				'type'  => 'email_color_palette',
				'id'    => 'email_color_palette',
			);

			$color_palette_section_end = array(
				'type' => 'sectionend',
				'id'   => 'email_template_options',
			);
		}

		// Reorder email color settings based on the email_improvements feature flag.

		$base_color_setting = array(
			'title'    => $base_color_title,
			'desc'     => $base_color_desc,
			'id'       => 'woocommerce_email_base_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => $base_color_default,
			'autoload' => false,
			'desc_tip' => true,
		);

		$bg_color_setting = array(
			'title'    => $bg_color_title,
			'desc'     => $bg_color_desc,
			'id'       => 'woocommerce_email_background_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => $bg_color_default,
			'autoload' => false,
			'desc_tip' => true,
		);

		$body_bg_color_setting = array(
			'title'    => $body_bg_color_title,
			'desc'     => $body_bg_color_desc,
			'id'       => 'woocommerce_email_body_background_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => $body_bg_color_default,
			'autoload' => false,
			'desc_tip' => true,
		);

		$body_text_color_setting = array(
			'title'    => $body_text_color_title,
			'desc'     => $body_text_color_desc,
			'id'       => 'woocommerce_email_text_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => $body_text_color_default,
			'autoload' => false,
			'desc_tip' => true,
		);

		$footer_text_color_setting = array(
			'title'    => $footer_text_color_title,
			'desc'     => $footer_text_color_desc,
			'id'       => 'woocommerce_email_footer_text_color',
			'type'     => 'color',
			'css'      => 'width:6em;',
			'default'  => $footer_text_color_default,
			'autoload' => false,
			'desc_tip' => true,
		);

		$reorder_colors = FeaturesUtil::feature_is_enabled( 'email_improvements' );

		$base_color_setting_in_template_opts        = $reorder_colors ? null : $base_color_setting;
		$bg_color_setting_in_template_opts          = $reorder_colors ? null : $bg_color_setting;
		$body_bg_color_setting_in_template_opts     = $reorder_colors ? null : $body_bg_color_setting;
		$body_text_color_setting_in_template_opts   = $reorder_colors ? null : $body_text_color_setting;
		$footer_text_color_setting_in_template_opts = $reorder_colors ? null : $footer_text_color_setting;

		$base_color_setting_in_palette        = $reorder_colors ? $base_color_setting : null;
		$bg_color_setting_in_palette          = $reorder_colors ? $bg_color_setting : null;
		$body_bg_color_setting_in_palette     = $reorder_colors ? $body_bg_color_setting : null;
		$body_text_color_setting_in_palette   = $reorder_colors ? $body_text_color_setting : null;
		$footer_text_color_setting_in_palette = $reorder_colors ? $footer_text_color_setting : null;

		$settings =
			array(
				array(
					'title' => __( 'Email notifications', 'woocommerce' ),
					/* translators: %s: help description with link to WP Mail logging and support page. */
					'desc'  => sprintf( __( 'Email notifications sent from WooCommerce are listed below. Click on an email to configure it.<br>%s', 'woocommerce' ), $desc_help_text ),
					'type'  => 'title',
					'id'    => 'email_notification_settings',
				),

				array( 'type' => 'email_notification' ),

				array(
					'type' => 'sectionend',
					'id'   => 'email_notification_settings',
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_recipient_options',
				),

				array(
					'title' => __( 'Email sender options', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => __( "Set the name and email address you'd like your outgoing emails to use.", 'woocommerce' ),
					'id'    => 'email_options',
				),

				array(
					'title'    => __( '"From" name', 'woocommerce' ),
					'desc'     => '',
					'id'       => 'woocommerce_email_from_name',
					'type'     => 'text',
					'css'      => 'min-width:400px;',
					'default'  => esc_attr( get_bloginfo( 'name', 'display' ) ),
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'             => __( '"From" address', 'woocommerce' ),
					'desc'              => '',
					'id'                => 'woocommerce_email_from_address',
					'type'              => 'email',
					'custom_attributes' => array(
						'multiple' => 'multiple',
					),
					'css'               => 'min-width:400px;',
					'default'           => get_option( 'admin_email' ),
					'autoload'          => false,
					'desc_tip'          => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_options',
				),

				array(
					'title' => __( 'Email template', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => $email_template_description,
					'id'    => 'email_template_options',
				),

				$logo_image,

				$logo_image_width,

				$header_alignment,

				$font_family,

				$base_color_setting_in_template_opts,

				$bg_color_setting_in_template_opts,

				$body_bg_color_setting_in_template_opts,

				$body_text_color_setting_in_template_opts,

				array(
					'title'       => __( 'Footer text', 'woocommerce' ),
					'desc'        => $footer_text_description,
					'id'          => 'woocommerce_email_footer_text',
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'type'        => 'textarea',
					'default'     => $footer_text_default,
					'autoload'    => false,
					'desc_tip'    => true,
				),

				$footer_text_color_setting_in_template_opts,

				array(
					'type' => 'sectionend',
					'id'   => 'email_template_options',
				),

				$color_palette_section_header,

				$base_color_setting_in_palette,

				$bg_color_setting_in_palette,

				$body_bg_color_setting_in_palette,

				$body_text_color_setting_in_palette,

				$footer_text_color_setting_in_palette,

				array(
					'title'    => __( 'Auto-sync with theme', 'woocommerce' ),
					'desc'     => __( 'Automatically update email styles when theme styles change', 'woocommerce' ),
					'id'       => 'woocommerce_email_auto_sync_with_theme',
					'type'     => 'hidden',
					'default'  => 'no',
					'autoload' => false,
				),

				$color_palette_section_end,

				array( 'type' => 'email_preview' ),

				array(
					'title' => __( 'Store management insights', 'woocommerce' ),
					'type'  => 'title',
					'id'    => 'email_merchant_notes',
				),

				array(
					'title'         => __( 'Enable email insights', 'woocommerce' ),
					'desc'          => __( 'Receive email notifications with additional guidance to complete the basic store setup and helpful insights', 'woocommerce' ),
					'id'            => 'woocommerce_merchant_email_notifications',
					'type'          => 'checkbox',
					'checkboxgroup' => 'start',
					'default'       => 'no',
					'autoload'      => false,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_merchant_notes',
				),
			);

		// Remove empty elements that depend on the email_improvements feature flag.
		$settings = array_filter( $settings );

		return apply_filters( 'woocommerce_email_settings', $settings );
	}

	/**
	 * Get custom fonts for emails.
	 */
	public function get_custom_fonts() {
		$custom_fonts = array();
		if ( wc_current_theme_is_fse_theme() && class_exists( 'WP_Font_Face_Resolver' ) ) {
			$theme_fonts = WP_Font_Face_Resolver::get_fonts_from_theme_json();
			if ( count( $theme_fonts ) > 0 ) {
				foreach ( $theme_fonts as $font ) {
					if ( ! empty( $font[0]['font-family'] ) ) {
						$custom_fonts[ $font[0]['font-family'] ] = $font[0]['font-family'];
					}
				}
			}
		}
		ksort( $custom_fonts );

		return $custom_fonts;
	}

	/**
	 * Output the settings.
	 */
	public function output() {
		global $current_section;

		// Define emails that can be customised here.
		$mailer          = WC()->mailer();
		$email_templates = $mailer->get_emails();

		if ( $current_section ) {
			foreach ( $email_templates as $email_key => $email ) {
				if ( strtolower( $email_key ) === $current_section ) {
					$this->run_email_admin_options( $email );
					break;
				}
			}
		}

		parent::output();
	}

	/**
	 * Run the 'admin_options' method on a given email.
	 * This method exists to easy unit testing.
	 *
	 * @param object $email The email object to run the method on.
	 */
	protected function run_email_admin_options( $email ) {
		$email->admin_options();
	}

	/**
	 * Save settings.
	 */
	public function save() {
		global $current_section;

		if ( ! $current_section ) {
			$this->save_settings_for_current_section();
			$this->do_update_options_action();
		} else {
			$wc_emails = WC_Emails::instance();

			if ( in_array( $current_section, array_map( 'sanitize_title', array_keys( $wc_emails->get_emails() ) ), true ) ) {
				foreach ( $wc_emails->get_emails() as $email_id => $email ) {
					if ( sanitize_title( $email_id ) === $current_section ) {
						$this->do_update_options_action( $email->id );
					}
				}
			} else {
				$this->save_settings_for_current_section();
				$this->do_update_options_action();
			}
		}
	}

	/**
	 * Output email notification settings.
	 */
	public function email_notification_setting() {
		// Define emails that can be customised here.
		$mailer          = WC()->mailer();
		$email_templates = $mailer->get_emails();

		?>
		<tr valign="top">
		<td class="wc_emails_wrapper" colspan="2">
			<table class="wc_emails widefat" cellspacing="0">
				<thead>
					<tr>
						<?php
						$columns = apply_filters(
							'woocommerce_email_setting_columns',
							array(
								'status'     => '',
								'name'       => __( 'Email', 'woocommerce' ),
								'email_type' => __( 'Content type', 'woocommerce' ),
								'recipient'  => __( 'Recipient(s)', 'woocommerce' ),
								'actions'    => '',
							)
						);
						foreach ( $columns as $key => $column ) {
							echo '<th class="wc-email-settings-table-' . esc_attr( $key ) . '">' . esc_html( $column ) . '</th>';
						}
						?>
						</tr>
					</thead>
					<tbody>
						<?php
						foreach ( $email_templates as $email_key => $email ) {
							echo '<tr>';

							foreach ( $columns as $key => $column ) {

								switch ( $key ) {
									case 'name':
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
										<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=' . strtolower( $email_key ) ) ) . '">' . esc_html( $email->get_title() ) . '</a>
										' . wc_help_tip( $email->get_description() ) . '
										</td>';
										break;
									case 'recipient':
										$to  = $email->is_customer_email() ? __( 'Customer', 'woocommerce' ) : $email->get_recipient();
										$cc  = false;
										$bcc = false;
										if ( FeaturesUtil::feature_is_enabled( 'email_improvements' ) ) {
											$ccs  = $email->get_cc_recipient();
											$bccs = $email->get_bcc_recipient();
											// Translators: %s: comma-separated email addresses to which the email is cc-ed.
											$cc = $ccs ? sprintf( __( '<b>Cc</b>: %s', 'woocommerce' ), $ccs ) : false;
											// Translators: %s: comma-separated email addresses to which the email is bcc-ed.
											$bcc = $bccs ? sprintf( __( '<b>Bcc</b>: %s', 'woocommerce' ), $bccs ) : false;
											if ( $cc || $bcc ) {
												// Translators: %s: comma-separated email addresses to which the email is sent.
												$to = sprintf( __( '<b>To</b>: %s', 'woocommerce' ), $to );
											}
										}
										$allowed_tags = array( 'b' => array() );

										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';
										echo wp_kses( $to, $allowed_tags );
										if ( $cc ) {
											echo '<br>' . wp_kses( $cc, $allowed_tags );
										}
										if ( $bcc ) {
											echo '<br>' . wp_kses( $bcc, $allowed_tags );
										}
										echo '</td>';
										break;
									case 'status':
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">';

										if ( $email->is_manual() ) {
											echo '<span class="status-manual tips" data-tip="' . esc_attr__( 'Manually sent', 'woocommerce' ) . '">' . esc_html__( 'Manual', 'woocommerce' ) . '</span>';
										} elseif ( $email->is_enabled() ) {
											echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Enabled', 'woocommerce' ) . '">' . esc_html__( 'Yes', 'woocommerce' ) . '</span>';
										} else {
											echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Disabled', 'woocommerce' ) . '">-</span>';
										}

										echo '</td>';
										break;
									case 'email_type':
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
										' . esc_html( $email->get_content_type() ) . '
										</td>';
										break;
									case 'actions':
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
										<a class="button alignright" href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=email&section=' . strtolower( $email_key ) ) ) . '">' . esc_html__( 'Manage', 'woocommerce' ) . '</a>
										</td>';
										break;
									default:
										do_action( 'woocommerce_email_setting_column_' . $key, $email );
										break;
								}
							}

							echo '</tr>';
						}
						?>
					</tbody>
				</table>
			</td>
		</tr>
		<?php
	}

	/**
	 * Creates the React mount point for the email preview.
	 */
	public function email_preview() {
		$this->delete_transient_email_settings();
		$emails      = WC()->mailer()->get_emails();
		$email_types = array();
		foreach ( $emails as $email ) {
			$email_types[] = array(
				'label' => $email->get_title(),
				'value' => get_class( $email ),
			);
		}
		?>
		<div
			id="wc_settings_email_preview_slotfill"
			data-preview-url="<?php echo esc_url( wp_nonce_url( admin_url( '?preview_woocommerce_mail=true' ), 'preview-mail' ) ); ?>"
			data-email-types="<?php echo esc_attr( wp_json_encode( $email_types ) ); ?>"
			data-email-setting-ids="<?php echo esc_attr( wp_json_encode( EmailPreview::get_email_style_setting_ids() ) ); ?>"
		></div>
		<?php
	}

	/**
	 * Creates the React mount point for the single email preview.
	 *
	 * @param object $email The email object to run the method on.
	 */
	public function email_preview_single( $email ) {
		$this->delete_transient_email_settings();
		// Email types array should have a single entry for current email.
		$email_types = array(
			array(
				'label' => $email->get_title(),
				'value' => get_class( $email ),
			),
		);
		?>
		<h2><?php echo esc_html( __( 'Email preview', 'woocommerce' ) ); ?></h2>

		<p><?php echo esc_html( __( 'Preview your email template. You can also test on different devices and send yourself a test email.', 'woocommerce' ) ); ?></p>
		<div>
			<div
				id="wc_settings_email_preview_slotfill"
				data-preview-url="<?php echo esc_url( wp_nonce_url( admin_url( '?preview_woocommerce_mail=true' ), 'preview-mail' ) ); ?>"
				data-email-types="<?php echo esc_attr( wp_json_encode( $email_types ) ); ?>"
				data-email-setting-ids="<?php echo esc_attr( wp_json_encode( EmailPreview::get_email_content_setting_ids( $email->id ) ) ); ?>"
			></div>
			<input type="hidden" id="woocommerce_email_from_name" value="<?php echo esc_attr( get_option( 'woocommerce_email_from_name' ) ); ?>" />
			<input type="hidden" id="woocommerce_email_from_address" value="<?php echo esc_attr( get_option( 'woocommerce_email_from_address' ) ); ?>" />
		</div>
		<?php
	}

	/**
	 * Deletes transient with email settings used for live preview. This is to
	 * prevent conflicts where the preview would show values from previous session.
	 */
	private function delete_transient_email_settings() {
		$setting_ids = EmailPreview::get_all_email_setting_ids();
		foreach ( $setting_ids as $id ) {
			delete_transient( $id );
		}
	}

	/**
	 * Creates the React mount point for the email image url.
	 *
	 * @param array $value Field value array.
	 */
	public function email_image_url( $value ) {
		$option_value = $value['value'];
		if ( ! isset( $value['field_name'] ) ) {
			$value['field_name'] = $value['id'];
		}
		?>
		<tr class="<?php echo esc_attr( $value['row_class'] ); ?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wc_help_tip( $value['desc'] ); // WPCS: XSS ok. ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
				<input
					name="<?php echo esc_attr( $value['field_name'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					type="hidden"
					value="<?php echo esc_attr( $option_value ); ?>"
				/>
				<div
					id="wc_settings_email_image_url_slotfill"
					data-id="<?php echo esc_attr( $value['id'] ); ?>"
					data-image-url="<?php echo esc_attr( $option_value ); ?>"
				></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Sanitize email image URL.
	 *
	 * @param  string $value     Option value.
	 * @param  array  $option    Option name.
	 * @param  string $raw_value Raw value.
	 * @return string
	 */
	public function sanitize_email_header_image( $value, $option, $raw_value ) {
		return sanitize_url( $raw_value );
	}

	/**
	 * Creates the email font family field with custom font family applied to each option.
	 *
	 * @param array $value Field value array.
	 */
	public function email_font_family( $value ) {
		$option_value = $value['value'];
		// This is a temporary fix to prevent using custom fonts without fallback.
		$custom_fonts = null;

		?>
		<tr class="<?php echo esc_attr( $value['row_class'] ); ?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
			</th>
			<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
			<script type="text/javascript">
				function renderWithFont( node ) {
					if ( ! node.element || ! node.element.value ) return node.text;
					var $wrapper = jQuery( '<span></span>' );
					$wrapper.css( {'font-family': node.element.dataset['font-family'] || node.element.value} );
					$wrapper.text( node.text );
					return $wrapper;
				}
				function fontsSelect( selector ) {
					jQuery( selector ).selectWoo( {
						minimumResultsForSearch: Infinity,
						templateResult: renderWithFont
					} );
				}
				jQuery( document.body )
					.on( 'wc-enhanced-select-init', function() {
						fontsSelect( '#<?php echo esc_js( $value['id'] ); ?>' );
					} );
				</script>
				<select
					name="<?php echo esc_attr( $value['field_name'] ); ?>"
					id="<?php echo esc_attr( $value['id'] ); ?>"
					>
					<optgroup label="<?php echo esc_attr__( 'Standard fonts', 'woocommerce' ); ?>">
						<?php
						foreach ( EmailFont::$font as $key => $font_family ) {
							?>
							<option
								value="<?php echo esc_attr( $key ); ?>"
								data-font-family="<?php echo esc_attr( $font_family ); ?>"
								<?php selected( $option_value, (string) $key ); ?>
							><?php echo esc_html( $key ); ?></option>
							<?php
						}
						?>
					</optgroup>
					<?php if ( $custom_fonts ) : ?>
						<optgroup label="<?php echo esc_attr__( 'Custom fonts', 'woocommerce' ); ?>">
							<?php
							foreach ( $custom_fonts as $key => $val ) {
								?>
							<option
								value="<?php echo esc_attr( $key ); ?>"
								<?php selected( $option_value, (string) $key ); ?>
							><?php echo esc_html( $val ); ?></option>
								<?php
							}
							?>
						</optgroup>
					<?php endif; ?>
				</select>
			</td>
		</tr>
		<?php
	}

	/**
	 * Creates the React mount point for the email color palette title.
	 *
	 * @param array $value Field value array.
	 */
	public function email_color_palette( $value ) {
		$default_colors = EmailColors::get_default_colors();
		$auto_sync = get_option( EmailStyleSync::AUTO_SYNC_OPTION, 'no' );

		?>
		<hr class="wc-settings-email-color-palette-separator" />
		<div class="wc-settings-email-color-palette-header">
			<h2 class="wc-settings-email-color-palette-title"><?php echo esc_html( $value['title'] ); ?></h2>
			<div
				class="wc-settings-email-color-palette-buttons"
				id="wc_settings_email_color_palette_slotfill"
				data-default-colors="<?php echo esc_attr( wp_json_encode( $default_colors ) ); ?>"
				<?php echo wp_theme_has_theme_json() ? 'data-has-theme-json' : ''; ?>
			></div>
			<input
				type="hidden"
				name="woocommerce_email_auto_sync_with_theme"
				id="woocommerce_email_auto_sync_with_theme"
				value="<?php echo esc_attr( $auto_sync ); ?>"
			/>
		</div>
		<table class="form-table">
		<?php
	}

	/**
	 * Append email improvements prop to Tracks globally.
	 *
	 * @param array $event_properties Event properties array.
	 *
	 * @return array
	 */
	public function append_feature_email_improvements_to_tracks( $event_properties ) {
		if ( is_array( $event_properties ) ) {
			$is_email_improvements_enabled                  = FeaturesUtil::feature_is_enabled( 'email_improvements' );
			$event_properties['feature_email_improvements'] = $is_email_improvements_enabled ? 'enabled' : 'disabled';
		}
		return $event_properties;
	}

	/**
	 * Track email improvements feature change.
	 *
	 * @param string $feature_id The feature ID.
	 * @param bool   $enabled True if the feature is enabled, false if it is disabled.
	 */
	public function track_email_improvements_feature_change( $feature_id, $enabled ) {
		if ( 'email_improvements' === $feature_id ) {
			$current_date = gmdate( 'Y-m-d H:i:s' );
			if ( $enabled ) {
				$enabled_count = get_option( 'woocommerce_email_improvements_enabled_count', 0 );
				update_option( 'woocommerce_email_improvements_enabled_count', $enabled_count + 1 );
				add_option( 'woocommerce_email_improvements_first_enabled_at', $current_date );
				update_option( 'woocommerce_email_improvements_last_enabled_at', $current_date );
			} else {
				$disabled_count = get_option( 'woocommerce_email_improvements_disabled_count', 0 );
				update_option( 'woocommerce_email_improvements_disabled_count', $disabled_count + 1 );
				add_option( 'woocommerce_email_improvements_first_disabled_at', $current_date );
				update_option( 'woocommerce_email_improvements_last_disabled_at', $current_date );
			}
		}
	}
}

return new WC_Settings_Emails();
