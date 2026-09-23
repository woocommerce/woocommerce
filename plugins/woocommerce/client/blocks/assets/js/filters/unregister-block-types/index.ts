/**
 * External dependencies
 */
import { getBlockTypes, unregisterBlockType } from '@wordpress/blocks';
import domReady from '@wordpress/dom-ready';

/**
 * Internal dependencies
 */
import {
	POST_EDITOR_BLOCK_TYPES_TO_UNREGISTER,
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
		return POST_EDITOR_BLOCK_TYPES_TO_UNREGISTER.filter( ( blockType ) =>
			registeredBlockTypes.includes( blockType )
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
	getBlockTypesToUnregister().forEach( ( blockType ) => {
		unregisterBlockType( blockType );
	} );
} );
