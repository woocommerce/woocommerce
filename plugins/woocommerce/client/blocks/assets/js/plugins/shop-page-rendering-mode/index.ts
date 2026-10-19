/**
 * External dependencies
 */
import { STORE_PAGES } from '@woocommerce/settings';
import { store as coreStore } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

const ShopPageRenderingMode = () => {
	const { setRenderingMode } = useDispatch( editorStore );
	const shouldPreviewTemplate = useSelect( ( select ) => {
		const editor = select( editorStore );
		const shopPageId =
			'shop' in STORE_PAGES ? STORE_PAGES.shop?.id : undefined;

		const isBlockTheme =
			select( coreStore ).getCurrentTheme().is_block_theme;

		return (
			isBlockTheme &&
			!! shopPageId &&
			!! editor &&
			editor.getCurrentPostType() === 'page' &&
			Number( editor.getCurrentPostId() ) === shopPageId &&
			!! editor.getEditorSettings().supportsTemplateMode
		);
	}, [] );

	useEffect( () => {
		// Set the initial preview without overriding subsequent user toggles.
		if ( shouldPreviewTemplate ) {
			setRenderingMode( 'template-locked' );
		}
	}, [ shouldPreviewTemplate, setRenderingMode ] );

	return null;
};

registerPlugin( 'woocommerce-shop-page-rendering-mode', {
	render: ShopPageRenderingMode,
} );
