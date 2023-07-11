/**
 * External dependencies
 */
import { MenuItem } from '@wordpress/components';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

export const CopyAllContentMenuItem = () => {
	const recordClick = () => {
		recordEvent( 'product_iframe_editor_copy_all_content_menu_item_click' );
	};

	return (
		<MenuItem role="menuitem" onClick={ recordClick }>
			{ __( 'Copy all content', 'woocommerce' ) }
		</MenuItem>
	);
};
