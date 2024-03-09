<?php
namespace Automattic\WooCommerce\Blocks\Domain\Services;

use Automattic\WooCommerce\Blocks\Domain\Package;
use Automattic\WooCommerce\Blocks\Utils\CartCheckoutUtils;

/**
 * Service class for adding new-style Notices to WooCommerce core.
 *
 * @internal
 */
class Notices {
	/**
	 * Holds the Package instance
	 *
	 * @var Package
	 */
	private $package;

	/**
	 * Templates used for notices.
	 *
	 * @var array
	 */
	private $notice_templates = array(
		'notices/error.php',
		'notices/notice.php',
		'notices/success.php',
	);

	/**
	 * Constructor
	 *
	 * @param Package $package An instance of the package class.
	 */
	public function __construct( Package $package ) {
		$this->package = $package;
	}

	/**
	 * Initialize notice hooks.
	 */
	public function init() {
		add_action(
			'after_setup_theme',
			function() {
				/**
				 * Allow classic theme developers to opt-in to using block notices.
				 *
				 * @since 8.8.0
				 * @param bool $use_block_notices_in_classic_theme Whether to use block notices in classic theme.
				 * @return bool
				 */
				if ( wp_is_block_theme() || apply_filters( 'woocommerce_use_block_notices_in_classic_theme', false ) ) {
					add_filter( 'wc_get_template', [ $this, 'get_notices_template' ], 10, 5 );
				}
			}
		);

		add_filter( 'woocommerce_kses_notice_allowed_tags', [ $this, 'add_kses_notice_allowed_tags' ] );
		add_action( 'wp_head', [ $this, 'enqueue_notice_styles' ] );
	}

	/**
	 * Allow SVG icon in notices.
	 *
	 * @param array $allowed_tags Allowed tags.
	 * @return array
	 */
	public function add_kses_notice_allowed_tags( $allowed_tags ) {
		$svg_args = array(
			'svg'  => array(
				'aria-hidden' => true,
				'xmlns'       => true,
				'width'       => true,
				'height'      => true,
				'viewbox'     => true,
				'focusable'   => true,
			),
			'path' => array(
				'd' => true,
			),
		);
		return array_merge( $allowed_tags, $svg_args );
	}

	/**
	 * Replaces core notice templates with those from blocks.
	 *
	 * The new notice templates match block components with matching icons and styling. The differences are:
	 * 1. Core has notices for info, success, and error notices, blocks has notices for info, success, error,
	 * warning, and a default notice type.
	 * 2. The block notices use different CSS classes to the core notices. Core uses `woocommerce-message`, `is-info`
	 * and `is-error` classes, blocks uses `wc-block-components-notice-banner is-error`,
	 * `wc-block-components-notice-banner is-info`, and `wc-block-components-notice-banner is-success`.
	 * 3. The markup of the notices is different, with the block notices using SVG icons and a slightly different
	 * structure to accommodate this.
	 *
	 * @param string $template Located template path.
	 * @param string $template_name Template name.
	 * @param array  $args Template arguments.
	 * @param string $template_path Template path.
	 * @param string $default_path Default path.
	 * @return string
	 */
	public function get_notices_template( $template, $template_name, $args, $template_path, $default_path ) {
		$directory = get_stylesheet_directory();
		$file      = $directory . '/woocommerce/' . $template_name;
		if ( file_exists( $file ) ) {
			return $file;
		}

		if ( in_array( $template_name, $this->notice_templates, true ) ) {
			$template = $this->package->get_path( 'templates/block-' . $template_name );
			wp_enqueue_style( 'wc-blocks-style' );
		}

		return $template;
	}

	/**
	 * Replaces all notices with the new block based notices.
	 *
	 * @return void
	 */
	public function enqueue_notice_styles() {
		wp_enqueue_style( 'wc-blocks-style' );
	}
}
