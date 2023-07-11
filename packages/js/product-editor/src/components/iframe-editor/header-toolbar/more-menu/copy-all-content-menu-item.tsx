/**
 * External dependencies
 */
import { serialize } from '@wordpress/blocks';
import { MenuItem } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { createElement, useContext } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import { EditorContext } from '../../context';

export const CopyAllContentMenuItem = () => {
	const { createNotice } = useDispatch( 'core/notices' );
	const { blocks } = useContext( EditorContext );

	const getText = () => {
		return serialize( blocks );
	};

	const recordClick = () => {
		recordEvent( 'product_iframe_editor_copy_all_content_menu_item_click' );
	};

	const onCopySuccess = () => {
		createNotice( 'success', __( 'All content copied.', 'woocommerce' ) );
	};

	const ref = useCopyToClipboard( getText, onCopySuccess );

	return (
		<MenuItem
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore ref is okay here
			ref={ ref }
			role="menuitem"
			onClick={ recordClick }
		>
			{ __( 'Copy all content', 'woocommerce' ) }
		</MenuItem>
	);
};
