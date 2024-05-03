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
	 * Replaces all notices with the new block based notices.
	 *
	 * @return void
	 */
	public function enqueue_notice_styles() {
		wp_enqueue_style( 'wc-blocks-style' );
	}
}
