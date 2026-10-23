/**
 * External dependencies
 */
import { STORE_PAGES } from '@woocommerce/settings';
// Use the store name to avoid adding wp-editor as a dependency on widget screens.
import { CORE_EDITOR_STORE } from '@woocommerce/utils/wordpress-stores';
import { store as coreStore } from '@wordpress/core-data';
import { select as selectStore, useDispatch, useSelect } from '@wordpress/data';
import domReady from '@wordpress/dom-ready';
import { useEffect } from '@wordpress/element';
import { registerPlugin } from '@wordpress/plugins';

const ShopPageRenderingMode = () => {
	const { setRenderingMode } = useDispatch( CORE_EDITOR_STORE );
	const shouldPreviewTemplate = useSelect( ( select ) => {
		const editor = select( CORE_EDITOR_STORE );
		const shopPageId =
			'shop' in STORE_PAGES ? STORE_PAGES.shop?.id : undefined;

		const isBlockTheme =
			select( coreStore )?.getCurrentTheme()?.is_block_theme;

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

domReady( () => {
	if ( selectStore( CORE_EDITOR_STORE ) ) {
		registerPlugin( 'woocommerce-shop-page-rendering-mode', {
			render: ShopPageRenderingMode,
		} );
	}
} );
