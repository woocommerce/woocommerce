/**
 * External dependencies
 */
import { store as blockEditorStore } from '@wordpress/block-editor';
import { serialize } from '@wordpress/blocks';
import { MenuItem } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch, useSelect } from '@wordpress/data';
import { createElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

export const CopyAllContentMenuItem = () => {
	const { createNotice } = useDispatch( 'core/notices' );

	const { blocks } = useSelect( ( select ) => {
		const {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore These selectors are available in the block data store.
			getBlocks,
		} = select( blockEditorStore );

		return {
			blocks: getBlocks(),
		};
	}, [] );

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
			disabled={ ! blocks.length }
		>
			{ __( 'Copy all content', 'woocommerce' ) }
		</MenuItem>
	);
};
