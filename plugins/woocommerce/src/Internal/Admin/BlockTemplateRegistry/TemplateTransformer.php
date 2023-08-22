<?php

namespace Automattic\WooCommerce\Internal\Admin\BlockTemplateRegistry;

use Automattic\WooCommerce\Admin\BlockTemplates\BlockTemplateInterface;

/**
 * Template transformer.
 */
class TemplateTransformer {

    /**
	 * Transform the WooCommerceBlockTemplate to a WP_Block_Template.
	 *
	 * @param object $block_template The product template.
	 */
	public function transform( BlockTemplateInterface $block_template ): \WP_Block_Template {
		$template                 = new \WP_Block_Template();
		$template->id             = $block_template->get_id();
		$template->theme          = 'woocommerce/woocommerce';
		$template->content        = $block_template->get_formatted_template();
		$template->source         = 'plugin';
		$template->slug           = $block_template->get_id();
		$template->type           = 'wp_template';
		$template->title          = $block_template->get_title();
		$template->description    = $block_template->get_description();
		$template->status         = 'publish';
		$template->has_theme_file = true;
		$template->origin         = 'plugin';
		$template->is_custom      = false; // Templates loaded from the filesystem aren't custom, ones that have been edited and loaded from the DB are.
		$template->post_types     = array(); // Don't appear in any Edit Post template selector dropdown.
		$template->area           = $block_template->get_area();

		return $template;
	}

}