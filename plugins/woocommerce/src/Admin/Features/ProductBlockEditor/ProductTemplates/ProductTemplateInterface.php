<?php
/**
 * Product Block Editor product template interface.
 */

namespace Automattic\WooCommerce\Admin\Features\ProductBlockEditor\ProductTemplates;

/**
 * Product Block Editor product template interface.
 */
interface ProductTemplateInterface {

	/**
     * General group ID.
     */
    const GENERAL_GROUP = 'group/general';

	/**
     * Basic details ID.
     */
    const BASIC_DETAILS_SECTION = 'section/basic-details';

    /**
     * Pricing group ID.
     */
    const PRICING_GROUP = 'group/pricing';

	/**
     * Pricing section ID.
     */
    const PRICING_SECTION = 'section/pricing';

	/**
	 * Set up the template.
	 */
	public function __construct();

	/**
	 * Get the name of the template.
	 *
	 * @return string Template name
	 */
	public function get_name();

	/**
	 * Get the template layout.
	 *
	 * @return array Array of blocks
	 */
	public function get_template();

}
