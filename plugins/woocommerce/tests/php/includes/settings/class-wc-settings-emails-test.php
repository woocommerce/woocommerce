<?php
/**
 * Class WC_Settings_Emails_Test file.
 *
 * @package WooCommerce\Tests\Settings
 */

declare( strict_types = 1 );

use Automattic\WooCommerce\Internal\Admin\EmailPreview\EmailPreview;
use Automattic\WooCommerce\Internal\Email\EmailColors;
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
	 * @testdox The email font setting renders every shipped font, with its stack, and the selected value.
	 */
	public function test_email_font_family_setting_contract(): void {
		// Spelled out rather than read from EmailFont so that dropping a font
		// from the shipped list fails here instead of shrinking both sides.
		$expected_fonts = array(
			'Arial'           => "Arial, 'Helvetica Neue', Helvetica, sans-serif",
			'Comic Sans MS'   => "'Comic Sans MS', 'Marker Felt-Thin', Arial, sans-serif",
			'Courier New'     => "'Courier New', Courier, 'Lucida Sans Typewriter', 'Lucida Typewriter', monospace",
			'Georgia'         => "Georgia, Times, 'Times New Roman', serif",
			'Helvetica'       => "'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif",
			'Lucida'          => "'Lucida Sans Unicode', 'Lucida Grande', sans-serif",
			'Tahoma'          => 'Tahoma, Verdana, Segoe, sans-serif',
			'Times New Roman' => "'Times New Roman', Times, Baskerville, Georgia, serif",
			'Trebuchet MS'    => "'Trebuchet MS', 'Lucida Grande', 'Lucida Sans Unicode', 'Lucida Sans', Tahoma, sans-serif",
			'Verdana'         => 'Verdana, Geneva, sans-serif',
		);

		$sut            = new WC_Settings_Emails();
		$settings_by_id = $this->index_settings_by_id( $sut->get_settings_for_section( '' ) );
		$setting        = $settings_by_id['woocommerce_email_font_family'];

		$this->assertSame( 'Font family', $setting['title'] );
		$this->assertSame( 'email_font_family', $setting['type'] );
		$this->assertSame( 'Helvetica', $setting['default'] );

		// The settings page renders a row by dispatching its type through this
		// action, so the select below never reaches the page without the hook.
		$this->assertSame(
			10,
			has_action( 'woocommerce_admin_field_email_font_family', array( $sut, 'email_font_family' ) ),
			'The font family row is rendered from woocommerce_admin_field_email_font_family.'
		);

		$setting['field_name'] = $setting['id'];
		$setting['value']      = 'Georgia';

		ob_start();
		try {
			$sut->email_font_family( $setting );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document = $this->load_html_document( '<table>' . $output . '</table>' );
		$select   = $this->get_element_by_id( $document, 'woocommerce_email_font_family' );

		$options = $select->getElementsByTagName( 'option' );
		$this->assertCount( count( $expected_fonts ), $options );

		$rendered_fonts = array();
		$rendered_names = array();
		$selected       = array();
		foreach ( $options as $option ) {
			$rendered_fonts[ $option->getAttribute( 'value' ) ] = $option->getAttribute( 'data-font-family' );
			$rendered_names[]                                   = $option->textContent; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMNode defines this public property name.
			if ( $option->hasAttribute( 'selected' ) ) {
				$selected[] = $option->getAttribute( 'value' );
			}
		}

		$this->assertSame( $expected_fonts, $rendered_fonts );
		$this->assertSame( array_keys( $expected_fonts ), $rendered_names );
		$this->assertSame( array( 'Georgia' ), $selected );
	}

	/**
	 * @testdox The color palette settings row carries the title the palette section heading renders.
	 */
	public function test_email_color_palette_setting_row_contract(): void {
		$sut      = new WC_Settings_Emails();
		$settings = $sut->get_settings_for_section( '' );

		// Indexing by ID is not enough here: the sectionend that closes the
		// palette section reuses the same ID.
		$rows = array_values(
			array_filter(
				$settings,
				function ( $setting ) {
					return 'email_color_palette' === ( $setting['id'] ?? '' )
						&& 'email_color_palette' === ( $setting['type'] ?? '' );
				}
			)
		);

		$this->assertCount( 1, $rows, 'The default section defines exactly one color palette row.' );
		$this->assertSame( 'Color palette', $rows[0]['title'] );

		// The settings page renders a row by dispatching its type through this
		// action, so the heading below never reaches the page without the hook.
		$this->assertSame(
			10,
			has_action( 'woocommerce_admin_field_email_color_palette', array( $sut, 'email_color_palette' ) ),
			'The color palette row is rendered from woocommerce_admin_field_email_color_palette.'
		);

		ob_start();
		try {
			$sut->email_color_palette( $rows[0] );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
		}

		$document = $this->load_html_document( $output . '</table>' );
		$headings = $document->getElementsByTagName( 'h2' );

		$this->assertGreaterThan( 0, $headings->length, 'The palette section renders its heading.' );
		$this->assertSame( 'Color palette', $headings->item( 0 )->textContent ); // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- DOMNode defines this public property name.
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
		// desc_tip is what routes desc into the help tip the deleted E2E title read.
		// Setting it to a string is a supported form that replaces the tooltip text,
		// which would drop the placeholder hints while the two assertions above stay
		// green, so pin the boolean rather than just the description.
		$this->assertTrue( $setting['desc_tip'] );
	}

	/**
	 * @testdox A single email preview hangs off the email settings screen and renders its exact type, content settings, URL, and sender values.
	 */
	public function test_email_preview_single_contract(): void {
		update_option( 'woocommerce_email_from_name', 'Woo Test Store' );
		update_option( 'woocommerce_email_from_address', 'orders@example.com' );

		$email = WC_Emails::instance()->get_emails()[ WC_Email_Customer_Processing_Order::class ];
		$sut   = new WC_Settings_Emails();

		// The per-email settings screen renders the preview through this action,
		// so the mount below never reaches the page without the hook.
		$this->assertSame(
			10,
			has_action( 'woocommerce_email_settings_after', array( $sut, 'email_preview_single' ) ),
			'The single email preview is hooked onto woocommerce_email_settings_after.'
		);

		ob_start();
		try {
			$sut->email_preview_single( $email );
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
	}

	/**
	 * @testdox The email color palette prints the React mount the settings script reads, with the default colors and the theme.json flag.
	 *
	 * @testWith ["twentytwentyfour", true]
	 *           ["storefront", false]
	 *
	 * @param string $theme          Theme to activate.
	 * @param bool   $has_theme_json Whether that theme ships a theme.json.
	 */
	public function test_email_color_palette_mount_contract( string $theme, bool $has_theme_json ): void {
		update_option( 'woocommerce_feature_email_improvements_enabled', 'yes' );
		$original_theme = get_stylesheet();

		// switch_theme() writes options the rollback reverts, but the active theme is
		// also read back through in-memory caches, so put it back by hand.
		switch_theme( $theme );
		// The defaults follow the active theme's palette, so read them while it is active.
		$expected_colors = EmailColors::get_default_colors( true );
		ob_start();
		try {
			( new WC_Settings_Emails() )->email_color_palette( array( 'title' => 'Color palette' ) );
			$output = (string) ob_get_contents();
		} finally {
			ob_end_clean();
			switch_theme( $original_theme );
		}

		// The method opens a form table for the color fields that follow it.
		$document = $this->load_html_document( $output . '</table>' );
		$mount    = $this->get_element_by_id( $document, 'wc_settings_email_color_palette_slotfill' );

		$this->assertSame( $expected_colors, json_decode( $mount->getAttribute( 'data-default-colors' ), true ) );
		$this->assertSame( $has_theme_json, $mount->hasAttribute( 'data-has-theme-json' ) );
		$this->assertSame( 'no', $this->get_element_by_id( $document, 'woocommerce_email_auto_sync_with_theme' )->getAttribute( 'value' ) );
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
