<?php
/**
 * This file is part of the WooCommerce Email Editor package.
 *
 * @package Automattic\WooCommerce\EmailEditor
 */

declare(strict_types = 1);
namespace Automattic\WooCommerce\EmailEditor\Engine\Renderer;

// require_once __DIR__ . '/../../../vendor/autoload.php'; // wrong vendor path. TODO: need to fix this.

use Automattic\WooCommerce\EmailEditor\Engine\Renderer\ContentRenderer\Content_Renderer;
use Automattic\WooCommerce\EmailEditor\Engine\Templates\Templates;
use Automattic\WooCommerce\EmailEditor\Engine\Theme_Controller;
use Soundasleep\Html2Text;
use WP_Style_Engine;

/**
 * Class Renderer
 */
class Renderer {
	/**
	 * Theme controller
	 *
	 * @var Theme_Controller
	 */
	private Theme_Controller $theme_controller;

	/**
	 * Content renderer
	 *
	 * @var Content_Renderer
	 */
	private Content_Renderer $content_renderer;

	/**
	 * Templates
	 *
	 * @var Templates
	 */
	private Templates $templates;

	/**
	 * Css inliner
	 *
	 * @var Css_Inliner
	 */
	private Css_Inliner $css_inliner;

	const TEMPLATE_FILE        = 'template-canvas.php';
	const TEMPLATE_STYLES_FILE = 'template-canvas.css';


	/**
	 * Renderer constructor.
	 *
	 * @param Content_Renderer $content_renderer Content renderer.
	 * @param Templates        $templates Templates.
	 * @param Css_Inliner      $css_inliner CSS Inliner.
	 * @param Theme_Controller $theme_controller Theme controller.
	 */
	public function __construct(
		Content_Renderer $content_renderer,
		Templates $templates,
		Css_Inliner $css_inliner,
		Theme_Controller $theme_controller
	) {
		$this->content_renderer = $content_renderer;
		$this->templates        = $templates;
		$this->theme_controller = $theme_controller;
		$this->css_inliner      = $css_inliner;
	}

	/**
	 * Renders the email template
	 *
	 * @param \WP_Post $post Post object.
	 * @param string   $subject Email subject.
	 * @param string   $pre_header Email preheader.
	 * @param string   $language Email language.
	 * @param string   $meta_robots Email meta robots.
	 * @return array
	 */
	public function render( \WP_Post $post, string $subject, string $pre_header, string $language, $meta_robots = '' ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$template_slug = get_page_template_slug( $post ) ? get_page_template_slug( $post ) : 'email-general';
		/** @var \WP_Block_Template $template */ // phpcs:ignore Generic.Commenting.DocComment.MissingShort -- used for phpstan
		$template = $this->templates->get_block_template( $template_slug );

		$email_styles  = $this->theme_controller->get_styles();
		$template_html = $this->content_renderer->render( $post, $template );
		$layout        = $this->theme_controller->get_layout_settings();

		ob_start();
		include self::TEMPLATE_FILE;
		$rendered_template = (string) ob_get_clean();

		$template_styles   =
		WP_Style_Engine::compile_css(
			array(
				'background-color' => $email_styles['color']['background'] ?? 'inherit',
				'color'            => $email_styles['color']['text'] ?? 'inherit',
				'padding-top'      => $email_styles['spacing']['padding']['top'] ?? '0px',
				'padding-bottom'   => $email_styles['spacing']['padding']['bottom'] ?? '0px',
				'padding-left'     => $email_styles['spacing']['padding']['left'] ?? '0px',
				'padding-right'    => $email_styles['spacing']['padding']['right'] ?? '0px',
				'font-family'      => $email_styles['typography']['fontFamily'] ?? 'inherit',
				'line-height'      => $email_styles['typography']['lineHeight'] ?? '1.5',
				'font-size'        => $email_styles['typography']['fontSize'] ?? 'inherit',
			),
			'body, .email_layout_wrapper'
		);
		$template_styles  .= '.email_layout_wrapper { box-sizing: border-box;}';
		$template_styles  .= file_get_contents( __DIR__ . '/' . self::TEMPLATE_STYLES_FILE );
		$template_styles   = '<style>' . wp_strip_all_tags( (string) apply_filters( 'woocommerce_email_renderer_styles', $template_styles, $post ) ) . '</style>';
		$rendered_template = $this->inline_css_styles( $template_styles . $rendered_template );

		// This is a workaround to support link :hover in some clients. Ideally we would remove the ability to set :hover
		// however this is not possible using the color panel from Gutenberg.
		if ( isset( $email_styles['elements']['link'][':hover']['color']['text'] ) ) {
			$rendered_template = str_replace( '<!-- Forced Styles -->', '<style>a:hover { color: ' . esc_attr( $email_styles['elements']['link'][':hover']['color']['text'] ) . ' !important; }</style>', $rendered_template );
		}

		return array(
			'html' => $rendered_template,
			'text' => $this->render_text_version( $rendered_template ),
		);
	}

	/**
	 * Inlines CSS styles into the HTML
	 *
	 * @param string $template HTML template.
	 * @return string
	 */
	private function inline_css_styles( $template ) {
		return $this->css_inliner->from_html( $template )->inline_css()->render();
	}

	/**
	 * Renders the text version of the email template
	 *
	 * @param string $template HTML template.
	 * @return string
	 */
	private function render_text_version( $template ) {
		$template = ( mb_detect_encoding( $template, 'UTF-8', true ) ) ? $template : mb_convert_encoding( $template, 'UTF-8', mb_list_encodings() );
		$result   = Html2Text::convert( $template );
		if ( ! $result ) {
			return '';
		}

		return $result;
	}
}
