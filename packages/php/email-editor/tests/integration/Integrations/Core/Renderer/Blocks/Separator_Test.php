<?php
/**
 * This file is part of the WooCommerce Email Editor package
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Integrations\Core\Renderer\Blocks;

use Automattic\WooCommerce\EmailEditor\Engine\Email_Editor;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Rendering_Context;
use Automattic\WooCommerce\EmailEditor\Engine\Renderer\Html2Text;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;

/**
 * Integration test for Separator class
 */
class Separator_Test extends \Email_Editor_Integration_Test_Case {
	/**
	 * Separator renderer instance
	 *
	 * @var Separator
	 */
	private $separator_renderer;

	/**
	 * Instance of Rendering_Context class
	 *
	 * @var Rendering_Context
	 */
	private $rendering_context;

	/**
	 * Set up before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->di_container->get( Email_Editor::class )->initialize();
		$this->separator_renderer = new Separator();
		$theme_controller         = $this->di_container->get( Theme_Controller::class );
		$this->rendering_context  = new Rendering_Context( $theme_controller->get_theme() );
	}

	/**
	 * Renders a separator block with the given attributes and saved markup.
	 *
	 * @param array  $attrs Block attributes.
	 * @param string $html Saved block markup.
	 * @param array  $email_attrs Email attributes added by the preprocessors.
	 */
	private function render( array $attrs, string $html = '<hr class="wp-block-separator has-alpha-channel-opacity"/>', array $email_attrs = array() ): string {
		$parsed_block = array(
			'blockName'    => 'core/separator',
			'attrs'        => $attrs,
			'email_attrs'  => $email_attrs,
			'innerBlocks'  => array(),
			'innerHTML'    => $html,
			'innerContent' => array( $html ),
		);
		$rendered     = $this->separator_renderer->render( $html, $parsed_block, $this->rendering_context );
		$this->checkValidHTML( $rendered );
		return $rendered;
	}

	/**
	 * The default style is a short centered line in the inherited text color.
	 */
	public function testItRendersDefaultStyleAsShortCenteredLine(): void {
		$rendered = $this->render( array(), '<hr class="wp-block-separator has-alpha-channel-opacity"/>', array( 'color' => '#123456' ) );

		$this->assertStringContainsString( 'align="center"', $rendered );
		$this->assertStringContainsString( 'width="100"', $rendered );
		$this->assertStringContainsString( 'border-top:2px solid #123456', $rendered );
		$this->assertStringNotContainsString( '<hr', $rendered );
	}

	/**
	 * The plain-text email shows the line as a row of dashes, as it would for an <hr>.
	 */
	public function testItRendersLineAsDashesInPlainText(): void {
		$text = Html2Text::convert( '<p>Above</p>' . $this->render( array() ) . '<p>Below</p>' );

		$this->assertMatchesRegularExpression( '/Above\s+-{20,}\s+Below/', $text );
	}

	/**
	 * The wide style spans the full content width.
	 */
	public function testItRendersWideStyleAtFullWidth(): void {
		$rendered = $this->render(
			array( 'className' => 'is-style-wide' ),
			'<hr class="wp-block-separator has-alpha-channel-opacity is-style-wide"/>'
		);

		$this->assertStringContainsString( 'width="100%"', $rendered );
		$this->assertStringNotContainsString( 'width="100"', $rendered );
		$this->assertStringContainsString( 'border-top:2px solid', $rendered );
	}

	/**
	 * A palette color picked in the editor colors the line.
	 */
	public function testItUsesPaletteColor(): void {
		$rendered = $this->render(
			array( 'backgroundColor' => 'vivid-red' ),
			'<hr class="wp-block-separator has-text-color has-vivid-red-color has-alpha-channel-opacity has-vivid-red-background-color has-background"/>'
		);

		$this->assertStringContainsString( 'border-top:2px solid #cf2e2e', $rendered );
	}

	/**
	 * A custom color picked in the editor colors the line.
	 */
	public function testItUsesCustomColor(): void {
		$rendered = $this->render(
			array( 'style' => array( 'color' => array( 'background' => '#00ff00' ) ) ),
			'<hr class="wp-block-separator has-text-color has-alpha-channel-opacity has-background" style="background-color:#00ff00;color:#00ff00"/>'
		);

		$this->assertStringContainsString( 'border-top:2px solid #00ff00', $rendered );
	}

	/**
	 * A see-through color is blended onto the email background, since Outlook can’t read hex with alpha.
	 */
	public function testItBlendsTransparentColorOntoEmailBackground(): void {
		$rendered = $this->render(
			array( 'style' => array( 'color' => array( 'background' => '#cf2e2e80' ) ) )
		);
		$this->assertStringContainsString( 'border-top:2px solid #e79696', $rendered );
	}

	/**
	 * Short hex and rgba() colors with alpha are blended too, and every hex form ends up as 6 digits.
	 *
	 * @dataProvider alpha_color_provider
	 * @param string $color Picked color.
	 * @param string $expected Blended color.
	 */
	public function testItBlendsOtherAlphaColorFormats( string $color, string $expected ): void {
		$rendered = $this->render( array( 'style' => array( 'color' => array( 'background' => $color ) ) ) );

		$this->assertStringContainsString( 'border-top:2px solid ' . $expected . ';', $rendered );
	}

	/**
	 * Alpha colors in other formats and their blend onto white.
	 */
	public function alpha_color_provider(): array {
		return array(
			'four-digit hex' => array( '#f008', '#ff7777' ),
			'rgba()'         => array( 'rgba(255, 0, 0, 0.5)', '#ff8080' ),
			'opaque #rgba'   => array( '#f00f', '#ff0000' ),
			'opaque 8-digit' => array( '#ff0000ff', '#ff0000' ),
		);
	}

	/**
	 * A three-digit email background is used for the blend, not replaced by white.
	 */
	public function testItBlendsOntoShortHexBackground(): void {
		$theme = clone $this->di_container->get( Theme_Controller::class )->get_theme();
		$theme->merge(
			new \WP_Theme_JSON(
				array(
					'version' => 3,
					'styles'  => array( 'color' => array( 'background' => '#000' ) ),
				),
				'custom'
			)
		);
		$this->rendering_context = new Rendering_Context( $theme );

		$rendered = $this->render( array( 'style' => array( 'color' => array( 'background' => '#ff000080' ) ) ) );

		$this->assertStringContainsString( 'border-top:2px solid #800000', $rendered );
	}

	/**
	 * A palette color as the email background is resolved before blending, not replaced by white.
	 */
	public function testItBlendsOntoPaletteBackground(): void {
		$theme = clone $this->di_container->get( Theme_Controller::class )->get_theme();
		$theme->merge(
			new \WP_Theme_JSON(
				array(
					'version' => 3,
					'styles'  => array( 'color' => array( 'background' => 'var:preset|color|black' ) ),
				),
				'custom'
			)
		);
		$this->rendering_context = new Rendering_Context( $theme );

		$rendered = $this->render( array( 'style' => array( 'color' => array( 'background' => '#ffffff80' ) ) ) );

		$this->assertStringContainsString( 'border-top:2px solid #808080;', $rendered );
	}

	/**
	 * The Wide line keeps its width in the Gmail app, which shrinks tables without a minimum width.
	 */
	public function testItKeepsWideLineFromShrinkingInGmail(): void {
		$rendered = $this->render( array( 'className' => 'is-style-wide' ) );

		$this->assertStringContainsString( 'min-width:100%', $rendered );
	}

	/**
	 * The plain-text email shows the dots as a row of dashes too.
	 */
	public function testItRendersDotsAsDashesInPlainText(): void {
		$text = Html2Text::convert( '<p>Above</p>' . $this->render( array( 'className' => 'is-style-dots' ) ) . '<p>Below</p>' );

		$this->assertMatchesRegularExpression( '/Above\s+-{20,}\s+Below/', $text );
	}

	/**
	 * A style is picked by its whole class name, not a class that starts with it.
	 */
	public function testItIgnoresClassesThatOnlyStartWithStyleName(): void {
		$rendered = $this->render( array( 'className' => 'is-style-wide-dashed' ) );

		$this->assertStringContainsString( 'width="100"', $rendered );
	}

	/**
	 * The dots style renders three centered dots in the picked color, without a line.
	 */
	public function testItRendersDotsStyle(): void {
		$rendered = $this->render(
			array(
				'className'       => 'is-style-dots',
				'backgroundColor' => 'vivid-red',
			),
			'<hr class="wp-block-separator has-text-color has-vivid-red-color has-alpha-channel-opacity has-vivid-red-background-color has-background is-style-dots"/>',
			array( 'width' => '600px' )
		);

		$this->assertStringContainsString( '&#183;&#183;&#183;', $rendered );
		$this->assertStringContainsString( 'color:#cf2e2e', $rendered );
		$this->assertStringContainsString( 'font-size:24px', $rendered );
		// 2em would be 48px; the row is capped at 240px so it fits a phone screen.
		$this->assertStringContainsString( 'letter-spacing:42px', $rendered );
		$this->assertStringContainsString( 'align="center"', $rendered );
		$this->assertStringNotContainsString( 'border-top', $rendered );
	}

	/**
	 * Inside a block with its own font size (a Group), the dots follow that size.
	 */
	public function testItSizesDotsFromInheritedFontSize(): void {
		$rendered = $this->render(
			array( 'className' => 'is-style-dots' ),
			'<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>',
			array(
				'font-size' => '32px',
				'width'     => '600px',
			)
		);

		$this->assertStringContainsString( 'font-size:48px', $rendered );
		// 2em would be 96px; the row is capped at 240px so it fits a phone screen.
		$this->assertStringContainsString( 'letter-spacing:24px', $rendered );
	}

	/**
	 * A fluid (clamp) inherited font size is turned into its smallest px value, as for paragraphs.
	 */
	public function testItSizesDotsFromFluidInheritedFontSize(): void {
		$rendered = $this->render(
			array( 'className' => 'is-style-dots' ),
			'<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>',
			array( 'font-size' => 'clamp(20px, 2vw, 40px)' )
		);

		$this->assertStringContainsString( 'font-size:30px', $rendered );
	}

	/**
	 * In a narrow column the dot spacing shrinks to fit, so the column doesn’t grow.
	 */
	public function testItFitsDotsIntoNarrowColumn(): void {
		$rendered = $this->render(
			array( 'className' => 'is-style-dots' ),
			'<hr class="wp-block-separator has-alpha-channel-opacity is-style-dots"/>',
			array(
				'font-size' => '32px',
				'width'     => '200px',
			)
		);

		$this->assertStringContainsString( 'letter-spacing:14px', $rendered );
		$this->assertStringContainsString( 'padding-left:14px', $rendered );
	}

	/**
	 * A color that isn’t a valid CSS color never reaches the style attribute.
	 */
	public function testItDropsInvalidColor(): void {
		$rendered = $this->render(
			array( 'style' => array( 'color' => array( 'background' => 'red;background:url(x)' ) ) )
		);

		$this->assertStringNotContainsString( 'url(', $rendered );
		$this->assertStringContainsString( 'border-top:2px solid #000000', $rendered );
	}
}
