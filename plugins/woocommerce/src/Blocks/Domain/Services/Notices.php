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
		add_filter( 'woocommerce_kses_notice_allowed_tags', [ $this, 'add_kses_notice_allowed_tags' ] );
		add_filter( 'wc_get_template', [ $this, 'get_notices_template' ], 10, 5 );
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
	 * The new notice templates match block components with matching icons and styling. The only difference is that core
	 * only has notices for info, success, and error notices, whereas blocks has notices for info, success, error,
	 * warning, and a default notice type.
	 *
	 * @param string $template Located template path.
	 * @param string $template_name Template name.
	 * @param array  $args Template arguments.
	 * @param string $template_path Template path.
	 * @param string $default_path Default path.
	 * @return string
	 */
	public function get_notices_template( $template, $template_name, $args, $template_path, $default_path ) {
		if ( in_array( $template_name, $this->notice_templates, true ) ) {
			$template = $this->package->get_path( 'templates/' . $template_name );
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
