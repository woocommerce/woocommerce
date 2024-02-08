<?php
/**
 * WooCommerce Product Block Editor
 */

namespace Automattic\WooCommerce\Admin\Features\Assembler;

use Automattic\WooCommerce\Admin\Features\Assembler\AssemblerFonts;


/**
 * Loads assets related to the product block editor.
 */
class Init {

	/**
	 * Constructor
	 */
	public function __construct() {
		error_log( 'ProductBlockEditor Init' );
		new AssemblerFonts();
	}
}
