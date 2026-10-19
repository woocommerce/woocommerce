/**
 * External dependencies
 */
import { getBlockTypes, unregisterBlockType } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';
import { getSetting, STORE_PAGES } from '@woocommerce/settings';
import { select, subscribe } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

/**
 * Internal dependencies
 */
import {
	POST_EDITOR_BLOCK_TYPES_TO_UNREGISTER,
	SHOP_PAGE_TEMPLATE_BLOCK_TYPES,
	WIDGET_EDITOR_ALLOWED_BLOCK_TYPES,
} from './block-types';

type BlockEditorContext = 'post' | 'widgets' | 'other';

const getBlockEditorContext = (): BlockEditorContext => {
	const wordpressWindow = window as Window & {
		adminpage?: string;
		pagenow?: string;
	};
	const adminPage = wordpressWindow.adminpage;

	if ( [ 'post-php', 'post-new-php' ].includes( adminPage ?? '' ) ) {
		return 'post';
	}

	// Customizer controls do not load the admin header, so adminpage is absent.
	if (
		[ 'widgets-php', 'customize-php' ].includes( adminPage ?? '' ) ||
		wordpressWindow.pagenow === 'customize'
	) {
		return 'widgets';
	}

	return 'other';
};

const getBlockTypesToUnregister = (): string[] => {
	const registeredBlockTypes = getBlockTypes().map( ( { name } ) => name );
	const blockEditorContext = getBlockEditorContext();

	if ( blockEditorContext === 'post' ) {
		const shopPageId =
			'shop' in STORE_PAGES ? STORE_PAGES.shop?.id : undefined;
		const postId = select( editorStore )?.getCurrentPostId() ?? null;

		const isShopPageEditor =
			getSetting( 'currentThemeIsFSETheme', false ) &&
			!! shopPageId &&
			Number( postId ) === shopPageId;

		return POST_EDITOR_BLOCK_TYPES_TO_UNREGISTER.filter(
			( blockType ) =>
				registeredBlockTypes.includes( blockType ) &&
				! (
					isShopPageEditor &&
					SHOP_PAGE_TEMPLATE_BLOCK_TYPES.includes( blockType )
				)
		);
	}

	if ( blockEditorContext === 'widgets' ) {
		return registeredBlockTypes.filter(
			( blockType ) =>
				blockType.startsWith( 'woocommerce/' ) &&
				! WIDGET_EDITOR_ALLOWED_BLOCK_TYPES.includes( blockType )
		);
	}

	return [];
};

domReady( () => {
	const unregisterBlocks = () => {
		getBlockTypesToUnregister().forEach( ( blockType ) => {
			unregisterBlockType( blockType );
		} );
	};

	if ( getBlockEditorContext() !== 'post' ) {
		unregisterBlocks();
		return;
	}

	// DOM readiness does not guarantee the editor has loaded its current post.
	const unsubscribe = subscribe( () => {
		if ( ! select( editorStore ).getCurrentPostId() ) {
			return;
		}
		unsubscribe();
		unregisterBlocks();
	}, editorStore );
} );
