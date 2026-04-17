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
		const { getBlocks } = select( blockEditorStore );

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
			// @ts-expect-error MenuItem's public type expects LegacyRef<HTMLButtonElement>, but useCopyToClipboard returns a broader Ref<HTMLElement>.
			ref={ ref }
			role="menuitem"
			onClick={ recordClick }
			disabled={ ! blocks.length }
		>
			{ __( 'Copy all content', 'woocommerce' ) }
		</MenuItem>
	);
};
