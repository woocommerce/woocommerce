<?php
/**
 * WooCommerce Email Settings
 *
 * @package WooCommerce\Admin
 * @version 2.1.0
 */

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
		parent::__construct();
	}

	/**
	 * Get own sections.
	 *
	 * @return array
	 */
	protected function get_own_sections() {
		return array(
			'' => __( 'General', 'woocommerce' ),
			'template' => __( 'Template design', 'woocommerce' ),
			'notifications' => __( 'Notifications', 'woocommerce' ),
		);
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	protected function get_settings_for_default_section() {

		$settings =
			array(
				array(
					'title' => __( 'Email sender options', 'woocommerce' ),
					'type'  => 'title',
					'desc'  => '',
					'id'    => 'email_options',
				),

				array(
					'title'    => __( '"From" name', 'woocommerce' ),
					'desc'     => __( 'How the sender name appears in outgoing WooCommerce emails.', 'woocommerce' ),
					'id'       => 'woocommerce_email_from_name',
					'type'     => 'text',
					'css'      => 'min-width:400px;',
					'default'  => esc_attr( get_bloginfo( 'name', 'display' ) ),
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'             => __( '"From" address', 'woocommerce' ),
					'desc'              => __( 'How the sender email appears in outgoing WooCommerce emails.', 'woocommerce' ),
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
					'id'   => 'email_template_options',
				),

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

		return apply_filters( 'woocommerce_email_settings', $settings );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	protected function get_settings_for_template_section() {
		$settings =
			array(
				array(
					'title' => __( 'Email template', 'woocommerce' ),
					'type'  => 'title',
					/* translators: %s: Nonced email preview link */
					'desc'  => sprintf( __( 'This section lets you customize the WooCommerce emails. <a href="%s" target="_blank">Click here to preview your email template</a>.', 'woocommerce' ), wp_nonce_url( admin_url( '?preview_woocommerce_mail=true' ), 'preview-mail' ) ),
					'id'    => 'email_template_options',
				),

				array(
					'title'       => __( 'Header image', 'woocommerce' ),
					'desc'        => __( 'Paste the URL of an image you want to show in the email header. Upload images using the media uploader (Media > Add New).', 'woocommerce' ),
					'id'          => 'woocommerce_email_header_image',
					'type'        => 'text',
					'css'         => 'min-width:400px;',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'default'     => '',
					'autoload'    => false,
					'desc_tip'    => true,
				),

				array(
					'title'    => __( 'Base color', 'woocommerce' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The base color for WooCommerce email templates. Default %s.', 'woocommerce' ), '<code>#7f54b3</code>' ),
					'id'       => 'woocommerce_email_base_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#7f54b3',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Background color', 'woocommerce' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The background color for WooCommerce email templates. Default %s.', 'woocommerce' ), '<code>#f7f7f7</code>' ),
					'id'       => 'woocommerce_email_background_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#f7f7f7',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Body background color', 'woocommerce' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The main body background color. Default %s.', 'woocommerce' ), '<code>#ffffff</code>' ),
					'id'       => 'woocommerce_email_body_background_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#ffffff',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'    => __( 'Body text color', 'woocommerce' ),
					/* translators: %s: default color */
					'desc'     => sprintf( __( 'The main body text color. Default %s.', 'woocommerce' ), '<code>#3c3c3c</code>' ),
					'id'       => 'woocommerce_email_text_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#3c3c3c',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'title'       => __( 'Footer text', 'woocommerce' ),
					/* translators: %s: Available placeholders for use */
					'desc'        => __( 'The text to appear in the footer of all WooCommerce emails.', 'woocommerce' ) . ' ' . sprintf( __( 'Available placeholders: %s', 'woocommerce' ), '{site_title} {site_url}' ),
					'id'          => 'woocommerce_email_footer_text',
					'css'         => 'width:400px; height: 75px;',
					'placeholder' => __( 'N/A', 'woocommerce' ),
					'type'        => 'textarea',
					'default'     => ' ',
					'autoload'    => false,
					'desc_tip'    => true,
				),

				array(
					'title'    => __( 'Footer text color', 'woocommerce' ),
					/* translators: %s: footer default color */
					'desc'     => sprintf( __( 'The footer text color. Default %s.', 'woocommerce' ), '<code>#3c3c3c</code>' ),
					'id'       => 'woocommerce_email_footer_text_color',
					'type'     => 'color',
					'css'      => 'width:6em;',
					'default'  => '#3c3c3c',
					'autoload' => false,
					'desc_tip' => true,
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_template_options',
				),

				array(
					'title' => __('Email Preview', 'woocommerce'),
					'type'  => 'title',
					'desc'  => __('Preview the selected email template with sample data.', 'woocommerce'),
					'id'    => 'email_preview_options'
				),

				array(
					'type' => 'email_preview',
					'id'   => 'woocommerce_email_preview'
				),

				array(
					'type' => 'sectionend',
					'id'   => 'email_preview_options'
				),
			);

		return apply_filters( 'woocommerce_email_settings_template', $settings );
	}

	/**
	 * Get settings array.
	 *
	 * @return array
	 */
	protected function get_settings_for_notifications_section() {
		$desc_help_text = sprintf(
		/* translators: %1$s: Link to WP Mail Logging plugin, %2$s: Link to Email FAQ support page. */
			__( 'To ensure your store&rsquo;s notifications arrive in your and your customers&rsquo; inboxes, we recommend connecting your email address to your domain and setting up a dedicated SMTP server. If something doesn&rsquo;t seem to be sending correctly, install the <a href="%1$s">WP Mail Logging Plugin</a> or check the <a href="%2$s">Email FAQ page</a>.', 'woocommerce' ),
			'https://wordpress.org/plugins/wp-mail-logging/',
			'https://woocommerce.com/document/email-faq'
		);
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
			);

		return apply_filters( 'woocommerce_email_settings_notifications', $settings );
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
										echo '<td class="wc-email-settings-table-' . esc_attr( $key ) . '">
										' . esc_html( $email->is_customer_email() ? __( 'Customer', 'woocommerce' ) : $email->get_recipient() ) . '
									</td>';
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

	public function email_preview() {
		?>
		<tr valign="top">
			<th scope="row">
				<label for="order_id">Order ID:</label><br>
				<input type="number" id="order_id" style="width:100%" /><br>
				<br>
				<label for="email_type">Email type:</label><br>
				<select id="email_type" style="width:100%;">
					<?php
					$emails = WC()->mailer()->get_emails();
					foreach ($emails as $type => $email) {
						echo '<option value="' . esc_attr($type) . '">' . esc_html($email->get_title()) . '</option>';
					}
					?>
				</select>
			</th>
			<td>
				<iframe id="email_preview" style="width:800px;border: 0;"></iframe>
				<script>
					document.addEventListener("DOMContentLoaded", function() {
						const emailPreviewUrl = '/wp-admin/?email_preview&order_id={orderId}&email_type={emailType}&styles={styles}';

						const orderId = document.getElementById('order_id');
						const emailType = document.getElementById('email_type');
						const iframe = document.getElementById('email_preview');

						const headerImg = document.getElementById('woocommerce_email_header_image');
						const baseColor = document.getElementById('woocommerce_email_base_color');
						const bgColor = document.getElementById('woocommerce_email_background_color');
						const bodyBgColor = document.getElementById('woocommerce_email_body_background_color');
						const textColor = document.getElementById('woocommerce_email_text_color');
						const footerText = document.getElementById('woocommerce_email_footer_text');
						const footerTextColor = document.getElementById('woocommerce_email_footer_text_color');

						let originalColors = [baseColor.value, bgColor.value, bodyBgColor.value, textColor.value, footerTextColor.value];

						const updateIframeUrl = function() {
							const styles = {
								header_img: headerImg.value,
								base_color: baseColor.value,
								bg_color: bgColor.value,
								body_bg_color: bodyBgColor.value,
								text_color: textColor.value,
								footer_text: footerText.value,
								footer_text_color: footerTextColor.value
							};
							const url = emailPreviewUrl
								.replace('{orderId}', orderId.value)
								.replace('{emailType}', emailType.value)
								.replace('{styles}', encodeURIComponent(JSON.stringify(styles)));
							iframe.src = url;
							iframe.onload = function() {
								iframe.style.height = iframe.contentWindow.document.body.scrollHeight + 'px';
							};
						};
						orderId.addEventListener('change', updateIframeUrl);
						emailType.addEventListener('change', updateIframeUrl);
						headerImg.addEventListener('change', updateIframeUrl);
						footerText.addEventListener('change', updateIframeUrl);
						baseColor.addEventListener('change', updateIframeUrl);
						bgColor.addEventListener('change', updateIframeUrl);
						bodyBgColor.addEventListener('change', updateIframeUrl);
						textColor.addEventListener('change', updateIframeUrl);
						footerTextColor.addEventListener('change', updateIframeUrl);

						// Color inputs don't fire `change` event when color is selected from the color picker
						// This is a workaround when the color is selected from the color picker
						setInterval(function() {
							const currentColors = [baseColor.value, bgColor.value, bodyBgColor.value, textColor.value, footerTextColor.value];
							if (originalColors.some((item, index) => item !== currentColors[index])) {
								originalColors = currentColors;
								updateIframeUrl();
							}
						}, 1000);

						updateIframeUrl();
					});
				</script>
			</td>
		</tr>
		<?php
	}
}

return new WC_Settings_Emails();
