<?php
/**
 * Tests for the order withdrawal form template.
 */

declare( strict_types = 1 );

/**
 * Order withdrawal form template test.
 */
class WC_Form_Order_Withdrawal_Template_Test extends WC_Unit_Test_Case {

	private const EMAIL = "o'neil--shop@48x17.com";

	/**
	 * @testdox The email reads as entered on the confirmation screen after wptexturize() runs over the template output.
	 */
	public function test_confirmation_email_survives_wptexturize(): void {
		$text = $this->render_texturized_text(
			array(
				'screen' => 'confirmation',
				'data'   => array( 'email' => self::EMAIL ),
			)
		);

		$this->assertStringContainsString( self::EMAIL, $text, 'The email in the confirmation note should not be texturized.' );
	}

	/**
	 * @testdox The email reads as entered in the review rows and note after wptexturize() runs over the template output.
	 */
	public function test_review_email_survives_wptexturize(): void {
		$text = $this->render_texturized_text(
			array(
				'screen'      => 'review',
				'data'        => array( 'email' => self::EMAIL ),
				'review_rows' => array(
					array(
						'label' => 'Email address',
						'value' => self::EMAIL,
					),
				),
			)
		);

		$this->assertSame( 2, substr_count( $text, self::EMAIL ), 'The email in the review row and the review note should not be texturized.' );
	}

	/**
	 * Render the template, texturize it as a block theme does, and return the visible text.
	 *
	 * @param array $args Template arguments.
	 */
	private function render_texturized_text( array $args ): string {
		$html = wc_get_template_html( 'myaccount/form-order-withdrawal.php', $args );

		return html_entity_decode( wp_strip_all_tags( wptexturize( $html ) ), ENT_QUOTES | ENT_HTML5 );
	}
}
