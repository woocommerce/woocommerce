/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useEffect } from '@wordpress/element';
// eslint-disable-next-line import/named -- These exports are re-exported from the package's api directory.
import { getPlugin, registerPlugin } from '@wordpress/plugins';
import { STORE_PAGES } from '@woocommerce/settings';

export function ShopPageRenderingMode() {
	const {
		postId,
		postType,
		isBlockTheme,
		isEditorReady,
		renderingMode,
		templateId,
	} = useSelect(
		( select ) => ( {
			postId: select( editorStore ).getCurrentPostId(),
			postType: select( editorStore ).getCurrentPostType(),
			isEditorReady: select( editorStore ).__unstableIsEditorReady(),
			renderingMode: select( editorStore ).getRenderingMode(),
			templateId: select( editorStore ).getCurrentTemplateId(),
		} ),
		[]
	);
	const { setRenderingMode } = useDispatch( editorStore );
	const shopPageId = STORE_PAGES.shop?.id;

	useEffect( () => {
		const isShopPage =
			postType === 'page' &&
			shopPageId &&
			Number( postId ) === shopPageId;

		if (
			isShopPage &&
			isEditorReady &&
			templateId &&
			renderingMode !== 'template-locked'
		) {
			setRenderingMode( 'template-locked' );
		}
	}, [
		postId,
		postType,
		isBlockTheme,
		isEditorReady,
		templateId,
		renderingMode,
		shopPageId,
		setRenderingMode,
	] );

	return null;
}

const PLUGIN_NAME = 'woocommerce-shop-page-rendering-mode';

if ( ! getPlugin( PLUGIN_NAME ) ) {
	registerPlugin( PLUGIN_NAME, {
		render: ShopPageRenderingMode,
	} );
}
