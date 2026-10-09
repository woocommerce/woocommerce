/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';
import { getSetting, STORE_PAGES } from '@woocommerce/settings';

const ShopPageRenderingMode = () => {
	const { setRenderingMode } = useDispatch( editorStore );
	const shouldPreviewTemplate = useSelect( ( select ) => {
		const editor = select( editorStore );
		const shopPageId =
			'shop' in STORE_PAGES ? STORE_PAGES.shop?.id : undefined;

		return (
			getSetting( 'isBlockTheme', false ) &&
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
