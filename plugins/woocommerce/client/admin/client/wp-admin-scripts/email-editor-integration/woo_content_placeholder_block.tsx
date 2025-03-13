/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { WooContentPlaceholderEditContent } from './woo_content_placeholder_edit_content';

export const wooContentBlock = {
	title: __( 'Woo Email Content', 'woocommerce' ),
	category: 'text',
	attributes: {},
	edit: WooContentPlaceholderEditContent,
	save: () => <div>##WOO_CONTENT##</div>,
};
