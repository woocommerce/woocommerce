// This file acts as a way of adding JS integration support for the email editor package

/**
 * External dependencies
 */
import { addFilter } from '@wordpress/hooks';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { wooContentBlock } from './woo_content_placeholder_block';
import { NAME_SPACE } from './constants';
import { modifySidebar } from './sidebar_extension';

addFilter(
	'woocommerce_email_editor_send_button_label',
	NAME_SPACE,
	() => 'Save WooCommerce email template' // This is a temporary label to confirm the integration works, it will be updated in the future.
);

registerBlockType( 'woo/email-content', wooContentBlock );
modifySidebar();
