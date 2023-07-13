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
    const GENERAL_GROUP = 'general';

	/**
     * Basic details ID.
     */
    const BASIC_DETAILS_SECTION = 'section/basic-details';

	/**
     * Description section ID.
     */
    const DESCRIPTION_SECTION = 'section/description';

	/**
     * Images section ID.
     */
    const IMAGES_SECTION = 'section/images';

	/**
     * Organization section ID.
     */
    const ORGANIZATION_SECTION = 'section/organization';

	/**
     * Attributes section ID.
     */
    const ATTRIBUTES_SECTION = 'section/attributes';

    /**
     * Pricing group ID.
     */
    const PRICING_GROUP = 'pricing';

	/**
     * Pricing section ID.
     */
    const PRICING_SECTION = 'section/pricing';

	/**
	 * Set up the template.
	 */
	public function __construct();

	/**
	 * Get the slug of the template.
	 *
	 * @return string Template slug
	 */
	public function get_slug();

	/**
	 * Get the title of the template.
	 *
	 * @return string Template title
	 */
	public function get_title();

	/**
	 * Get the description for the template.
	 *
	 * @return string Template description
	 */
	public function get_description();

}
