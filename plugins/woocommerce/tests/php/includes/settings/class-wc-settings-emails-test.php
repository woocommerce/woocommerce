<?php
/**
 * Class WC_Settings_Emails_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\Email\EmailColors;
use Automattic\WooCommerce\Internal\Email\EmailFont;
use Automattic\WooCommerce\Testing\Tools\CodeHacking\Hacks\StaticMockerHack;

require_once __DIR__ . '/class-wc-settings-unit-test-case.php';

/**
 * Unit tests for the WC_Settings_Email class.
 */
class WC_Settings_Emails_Test extends WC_Settings_Unit_Test_Case {

	/**
	 * @testdox get_sections should get all the existing sections.
	 */
	public function test_get_sections() {
		$sut = new WC_Settings_Emails();

		$section_names = array_keys( $sut->get_sections() );

		$expected = array(
			'',
		);

		$this->assertEquals( $expected, $section_names );
	}

	/**
	 * get_settings should trigger the appropriate filter depending on the requested section name.
	 *
	 * @testWith ["", "woocommerce_email_settings"]
	 *
	 * @param string $section_name The section name to test getting the settings for.
	 * @param string $filter_name The name of the filter that is expected to be triggered.
	 */
	public function test_get_settings_triggers_filter( $section_name, $filter_name ) {
		$actual_settings_via_filter = null;

		add_filter(
			$filter_name,
			function ( $settings ) use ( &$actual_settings_via_filter ) {
				$actual_settings_via_filter = $settings;

				return $settings;
			},
			10,
			1
		);

		$sut = new WC_Settings_Emails();

		$actual_settings_returned = $sut->get_settings_for_section( $section_name );
		remove_all_filters( $filter_name );

		$this->assertSame( $actual_settings_returned, $actual_settings_via_filter );
	}

	/**
	 * @testdox get_settings('') should return all the settings for the default section.
	 */
	public function test_get_default_settings_returns_all_settings() {
		$sut = new WC_Settings_Emails();

		$settings              = $sut->get_settings_for_section( '' );
		$setting_ids_and_types = $this->get_ids_and_types( $settings );

		$expected = array(
			'email_notification_settings'             => array( 'title', 'sectionend' ),
			''                                        => array( 'email_notification', 'email_preview' ),
			'email_recipient_options'                 => 'sectionend',
			'email_options'                           => array( 'title', 'sectionend' ),
			'woocommerce_email_from_name'             => 'text',
			'woocommerce_email_from_address'          => 'email',
			'woocommerce_email_reply_to_enabled'      => 'checkbox',
			'woocommerce_email_reply_to_name'         => 'text',
			'woocommerce_email_reply_to_address'      => 'email',
			'email_template_options'                  => array( 'title', 'sectionend' ),
			'previewing_new_templates'                => 'previewing_new_templates',
			'woocommerce_email_header_image'          => 'email_image_url',
			'woocommerce_email_header_image_width'    => 'number',
			'woocommerce_email_header_alignment'      => 'select',
			'woocommerce_email_font_family'           => 'email_font_family',
			'woocommerce_email_footer_text'           => 'textarea',
			'email_color_palette'                     => array( 'email_color_palette', 'sectionend' ),
			'woocommerce_email_base_color'            => 'color',
			'woocommerce_email_background_color'      => 'color',
			'woocommerce_email_body_background_color' => 'color',
			'woocommerce_email_text_color'            => 'color',
			'woocommerce_email_footer_text_color'     => 'color',
			'woocommerce_email_auto_sync_with_theme'  => 'hidden',
			'email_improvements_button'               => 'email_improvements_button',
		);

		$this->assertEquals( $expected, $setting_ids_and_types );
	}

	/**
	 * @testdox Default email settings expose the current color palette contract.
	 */
	public function test_get_default_settings_exposes_current_color_palette_contract(): void {
		$settings       = ( new WC_Settings_Emails() )->get_settings_for_section( '' );
		$settings_by_id = $this->index_settings_by_id( $settings );
		$default_colors = EmailColors::get_default_colors();

		$expected = array(
			'woocommerce_email_base_color'            => array( 'Accent', $default_colors['base'] ),
			'woocommerce_email_background_color'      => array( 'Email background', $default_colors['bg'] ),
			'woocommerce_email_body_background_color' => array( 'Content background', $default_colors['body_bg'] ),
			'woocommerce_email_text_color'            => array( 'Heading & text', $default_colors['body_text'] ),
			'woocommerce_email_footer_text_color'     => array( 'Secondary text', $default_colors['footer_text'] ),
		);

		foreach ( $expected as $id => $contract ) {
			list( $title, $default ) = $contract;
			$this->assertSame( $title, $settings_by_id[ $id ]['title'] );
			$this->assertSame( $default, $settings_by_id[ $id ]['default'] );
		}

		$titles = array_column( $settings, 'title' );
		$this->assertEmpty(
			array_intersect(
				array( 'Base color', 'Background color', 'Body background color', 'Body text color', 'Footer text color' ),
				$titles
			)
		);
	}

	/**
	 * @testdox The email font setting renders every supported font and the selected value.
	 */
	public function test_email_font_family_setting_contract(): void {
		$settings_by_id = $this->index_settings_by_id( ( new WC_Settings_Emails() )->get_settings_for_section( '' ) );
		$setting        = $settings_by_id['woocommerce_email_font_family'];

		$this->assertSame( 'Font family', $setting['title'] );
		$this->assertSame( 'email_font_family', $setting['type'] );
		$this->assertSame( 'Helvetica', $setting['default'] );

		$setting['field_name'] = $setting['id'];
		$setting['value']      = 'Georgia';

		ob_start();
		try {
			( new WC_Settings_Emails() )->email_font_family( $setting );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document = $this->load_html_document( '<table>' . $output . '</table>' );
		$select   = $this->get_element_by_id( $document, 'woocommerce_email_font_family' );

		$options = $select->getElementsByTagName( 'option' );
		$this->assertCount( count( EmailFont::$font ), $options );

		$rendered_fonts = array();
		$selected       = array();
		foreach ( $options as $option ) {
			$rendered_fonts[ $option->getAttribute( 'value' ) ] = $option->getAttribute( 'data-font-family' );
			if ( $option->hasAttribute( 'selected' ) ) {
				$selected[] = $option->getAttribute( 'value' );
			}
		}

		$this->assertSame( EmailFont::$font, $rendered_fonts );
		$this->assertSame( array( 'Georgia' ), $selected );
	}

	/**
	 * @testdox The email footer setting exposes the current placeholder contract.
	 */
	public function test_email_footer_setting_contract(): void {
		$settings_by_id = $this->index_settings_by_id( ( new WC_Settings_Emails() )->get_settings_for_section( '' ) );
		$setting        = $settings_by_id['woocommerce_email_footer_text'];

		$this->assertSame( 'Footer text', $setting['title'] );
		$this->assertSame( 'textarea', $setting['type'] );
		$this->assertSame( '{site_title}<br />{store_address}', $setting['default'] );
		$this->assertSame( 'N/A', $setting['placeholder'] );
		$this->assertStringContainsString( '{store_address}', $setting['desc'] );
		$this->assertStringContainsString( '{store_email}', $setting['desc'] );
	}

	/**
	 * @testdox A single email preview renders its exact type, content settings, URL, and sender values.
	 */
	public function test_email_preview_single_contract(): void {
		$option_names        = array( 'woocommerce_email_from_name', 'woocommerce_email_from_address' );
		$previous            = array();
		$previous_transients = array();

		foreach ( $option_names as $option_name ) {
			$previous[ $option_name ] = get_option( $option_name, null );
		}
		foreach ( EmailPreview::get_all_email_setting_ids() as $transient_name ) {
			$previous_transients[ $transient_name ] = array(
				'value'   => get_option( '_transient_' . $transient_name, false ),
				'timeout' => get_option( '_transient_timeout_' . $transient_name, false ),
			);
		}

		try {
			update_option( 'woocommerce_email_from_name', 'Woo Test Store' );
			update_option( 'woocommerce_email_from_address', 'orders@example.com' );

			$email = WC_Emails::instance()->get_emails()[ WC_Email_Customer_Processing_Order::class ];

			ob_start();
			try {
				( new WC_Settings_Emails() )->email_preview_single( $email );
				$output = (string) ob_get_contents();
			} finally {
				ob_end_clean();
			}

			$document = $this->load_html_document( $output );
			$mount    = $this->get_element_by_id( $document, 'wc_settings_email_preview_slotfill' );

			$this->assertSame(
				array(
					array(
						'label' => $email->get_title(),
						'value' => WC_Email_Customer_Processing_Order::class,
					),
				),
				json_decode( $mount->getAttribute( 'data-email-types' ), true )
			);
			$this->assertSame(
				EmailPreview::get_email_content_setting_ids( $email->id ),
				json_decode( $mount->getAttribute( 'data-email-setting-ids' ), true )
			);
			$this->assertSame(
				html_entity_decode( wp_nonce_url( admin_url( '?preview_woocommerce_mail=true' ), 'preview-mail' ) ),
				$mount->getAttribute( 'data-preview-url' )
			);
			$this->assertSame( 'Woo Test Store', $this->get_element_by_id( $document, 'woocommerce_email_from_name' )->getAttribute( 'value' ) );
			$this->assertSame( 'orders@example.com', $this->get_element_by_id( $document, 'woocommerce_email_from_address' )->getAttribute( 'value' ) );
		} finally {
			foreach ( $previous_transients as $transient_name => $transient ) {
				delete_transient( $transient_name );
				if ( false !== $transient['value'] ) {
					add_option(
						'_transient_' . $transient_name,
						$transient['value'],
						'',
						false === $transient['timeout']
					);
				}
				if ( false !== $transient['timeout'] ) {
					add_option( '_transient_timeout_' . $transient_name, $transient['timeout'], '', false );
				}
			}
			foreach ( $previous as $option_name => $value ) {
				if ( null === $value ) {
					delete_option( $option_name );
				} else {
					update_option( $option_name, $value );
				}
			}
		}
	}

	/**
	 * @testdox get_settings('') should return reply-to settings when block email editor is enabled.
	 */
	public function test_get_default_settings_with_block_email_editor_enabled() {
		$previous_value = get_option( 'woocommerce_feature_block_email_editor_enabled', null );

		try {
			// Enable block email editor feature before any WooCommerce initialization.
			update_option( 'woocommerce_feature_block_email_editor_enabled', 'yes' );

			$sut                   = new WC_Settings_Emails();
			$settings              = $sut->get_settings_for_section( '' );
			$setting_ids_and_types = $this->get_ids_and_types( $settings );

			// Verify reply-to fields are present.
			$this->assertArrayHasKey( 'woocommerce_email_reply_to_enabled', $setting_ids_and_types );
			$this->assertEquals( 'checkbox', $setting_ids_and_types['woocommerce_email_reply_to_enabled'] );

			$this->assertArrayHasKey( 'woocommerce_email_reply_to_name', $setting_ids_and_types );
			$this->assertEquals( 'text', $setting_ids_and_types['woocommerce_email_reply_to_name'] );

			$this->assertArrayHasKey( 'woocommerce_email_reply_to_address', $setting_ids_and_types );
			$this->assertEquals( 'email', $setting_ids_and_types['woocommerce_email_reply_to_address'] );
		} finally {
			if ( null === $previous_value ) {
				delete_option( 'woocommerce_feature_block_email_editor_enabled' );
			} else {
				update_option( 'woocommerce_feature_block_email_editor_enabled', $previous_value );
			}
		}
	}

	/**
	 * @testDox When the current section is the name of an existing email, 'output' invokes that email's 'admin_options' method.
	 */
	public function test_output_is_done_via_admin_options_method_of_email_specified_as_settings_section() {
		global $current_section;
		$current_section = 'wc_email_new_order';

		$admin_options_invoked = false;
		$actual_email          = null;

		$sut = $this->getMockBuilder( WC_Settings_Emails::class )
					->setMethods( array( 'run_email_admin_options' ) )
					->getMock();

		$sut->method( 'run_email_admin_options' )
			->will(
				$this->returnCallback(
					function ( $email ) use ( &$admin_options_invoked, &$actual_email ) {
						$admin_options_invoked = true;
						$actual_email          = $email;
					}
				)
			);

		$sut->output();

		$this->assertTrue( $admin_options_invoked );
		$this->assertInstanceOf( WC_Email_New_Order::class, $actual_email );
	}

	/**
	 * @testDox 'save' will trigger 'save_settings_for_current_section_invoked', and the appropriate actions.
	 *
	 * @testWith ["wc_email_new_order", false]
	 *           ["", true]
	 *
	 * @param string $section_name The current section name.
	 * @param bool   $expect_save_settings_for_current_section Whether 'save_settings_for_current_section' is expected to be invoked or not.
	 */
	public function test_save_triggers_appropriate_methods_and_actions( $section_name, $expect_save_settings_for_current_section ) {
		global $current_section;
		$current_section = $section_name;

		$save_settings_for_current_section_invoked = false;

		$email = WC_Emails::instance()->get_emails()[ WC_Email_New_Order::class ];

		$emails = $this->getMockBuilder( WC_Emails::class )
								->setMethods( array( 'get_emails' ) )
								->getMock();

		$emails->method( 'get_emails' )
						->willReturn( array( WC_Email_New_Order::class => $email ) );

		StaticMockerHack::add_method_mocks(
			array(
				'WC_Emails' => array(
					'instance' => function () use ( $emails ) {
						return $emails;
					},
				),
			)
		);

		$sut = $this->getMockBuilder( WC_Settings_Emails::class )
						->setMethods( array( 'save_settings_for_current_section' ) )
						->getMock();

		$sut->method( 'save_settings_for_current_section' )
						->will(
							$this->returnCallback(
								function () use ( &$save_settings_for_current_section_invoked ) {
									$save_settings_for_current_section_invoked = true;
								}
							)
						);

		$sut->save();

		$this->assertEquals( $expect_save_settings_for_current_section, $save_settings_for_current_section_invoked );
		$this->assertEquals( '' === $section_name ? 0 : 1, did_action( 'woocommerce_update_options_email_new_order' ) );
	}

	/**
	 * Index settings that expose an ID.
	 *
	 * @param array[] $settings Settings definitions.
	 * @return array<string, array> Settings keyed by ID.
	 */
	private function index_settings_by_id( array $settings ): array {
		$indexed = array();

		foreach ( $settings as $setting ) {
			if ( ! empty( $setting['id'] ) ) {
				$indexed[ $setting['id'] ] = $setting;
			}
		}

		return $indexed;
	}

	/**
	 * Load rendered HTML into a DOM document.
	 *
	 * @param string $html Rendered HTML.
	 * @return DOMDocument Parsed document.
	 */
	private function load_html_document( string $html ): DOMDocument {
		$document                = new DOMDocument();
		$previous_libxml_setting = libxml_use_internal_errors( true );
		$loaded                  = $document->loadHTML( '<!DOCTYPE html><html><body>' . $html . '</body></html>' );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_libxml_setting );

		if ( ! $loaded ) {
			throw new RuntimeException( 'Rendered email settings markup should be parseable HTML.' );
		}

		return $document;
	}

	/**
	 * Get a required element from rendered settings markup.
	 *
	 * @param DOMDocument $document Parsed document.
	 * @param string      $id       Element ID.
	 * @return DOMElement Required element.
	 */
	private function get_element_by_id( DOMDocument $document, string $id ): DOMElement {
		$element = $document->getElementById( $id );

		if ( ! $element instanceof DOMElement ) {
			throw new RuntimeException( 'Expected rendered element was not found.' );
		}

		return $element;
	}
}
