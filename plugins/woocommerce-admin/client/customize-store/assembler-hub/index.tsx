// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { createContext, useEffect, useRef, useState } from '@wordpress/element';
import { dispatch } from '@wordpress/data';
import {
	__experimentalFetchLinkSuggestions as fetchLinkSuggestions,
	__experimentalFetchUrlData as fetchUrlData,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/core-data';
// eslint-disable-next-line @woocommerce/dependency-group
import {
	registerCoreBlocks,
	__experimentalGetCoreBlocks,
	// @ts-ignore No types for this exist yet.
} from '@wordpress/block-library';
import {
	getBlockType,
	// @ts-ignore No types for this exist yet.
	store as blocksStore,
} from '@wordpress/blocks';
// @ts-ignore No types for this exist yet.
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-ignore No types for this exist yet.
import { unlock } from '@wordpress/edit-site/build-module/lock-unlock';
// @ts-ignore No types for this exist yet.
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
// @ts-ignore No types for this exist yet.
import { store as preferencesStore } from '@wordpress/preferences';
// @ts-ignore No types for this exist yet.
import { store as editorStore } from '@wordpress/editor';
// @ts-ignore No types for this exist yet.
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
// @ts-ignore No types for this exist yet.
import { GlobalStylesProvider } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
import { MediaUpload } from '@wordpress/media-utils';
import { addFilter } from '@wordpress/hooks';

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';
import { Layout } from './layout';
import './style.scss';
import { PreloadFonts } from './preload-fonts';
import { GoBackWarningModal } from './go-back-warning-modal';
import { onBackButtonClicked } from '../utils';
import { getNewPath } from '@woocommerce/navigation';
import useBodyClass from '../hooks/use-body-class';

import './tracking';

const { RouterProvider } = unlock( routerPrivateApis );

addFilter(
	'editor.MediaUpload',
	'woo/customize-store/assembler-hub',
	() => MediaUpload
);
type CustomizeStoreComponentProps = Parameters< CustomizeStoreComponent >[ 0 ];

export const CustomizeStoreContext = createContext< {
	sendEvent: CustomizeStoreComponentProps[ 'sendEvent' ];
	context: CustomizeStoreComponentProps[ 'context' ];
	currentState: CustomizeStoreComponentProps[ 'currentState' ];
} >( {
	sendEvent: () => {},
	context: {} as CustomizeStoreComponentProps[ 'context' ],
	currentState: 'assemblerHub',
} );

export type events =
	| { type: 'FINISH_CUSTOMIZATION' }
	| { type: 'GO_BACK_TO_DESIGN_WITH_AI' }
	| { type: 'GO_BACK_TO_DESIGN_WITHOUT_AI' };

const initializeAssembleHub = () => {
	if ( ! window.wcBlockSettings ) {
		// eslint-disable-next-line no-console
		console.warn(
			'window.blockSettings not found. Skipping initialization.'
		);
		return;
	}

	// Set up the block editor settings.
	const settings = window.wcBlockSettings;
	settings.__experimentalFetchLinkSuggestions = (
		search: string,
		searchOptions: {
			isInitialSuggestions: boolean;
			type: 'attachment' | 'post' | 'term' | 'post-format';
			subtype: string;
			page: number;
			perPage: number;
		}
	) => fetchLinkSuggestions( search, searchOptions, settings );
	settings.__experimentalFetchRichUrlData = fetchUrlData;

	const reapplyBlockTypeFilters =
		// @ts-ignore No types for this exist yet.
		dispatch( blocksStore ).__experimentalReapplyBlockTypeFilters || // GB < 16.6
		// @ts-ignore No types for this exist yet.
		dispatch( blocksStore ).reapplyBlockTypeFilters; // GB >= 16.6
	reapplyBlockTypeFilters();

	const coreBlocks = __experimentalGetCoreBlocks().filter(
		( { name }: { name: string } ) =>
			name !== 'core/freeform' && ! getBlockType( name )
	);
	registerCoreBlocks( coreBlocks );

	// @ts-ignore No types for this exist yet.
	dispatch( blocksStore ).setFreeformFallbackBlockName( 'core/html' );

	// @ts-ignore No types for this exist yet.
	dispatch( preferencesStore ).setDefaults( 'core/edit-site', {
		editorMode: 'visual',
		fixedToolbar: false,
		focusMode: false,
		distractionFree: false,
		keepCaretInsideBlock: false,
		welcomeGuide: false,
		welcomeGuideStyles: false,
		welcomeGuidePage: false,
		welcomeGuideTemplate: false,
		showListViewByDefault: false,
		showBlockBreadcrumbs: true,
	} );

	// @ts-ignore No types for this exist yet.
	dispatch( editSiteStore ).updateSettings( settings );

	// @ts-ignore No types for this exist yet.
	dispatch( editorStore ).updateEditorSettings( {
		defaultTemplateTypes: settings.defaultTemplateTypes,
		defaultTemplatePartAreas: settings.defaultTemplatePartAreas,
	} );

	// Prevent the default browser action for files dropped outside of dropzones.
	window.addEventListener( 'dragover', ( e ) => e.preventDefault(), false );
	window.addEventListener( 'drop', ( e ) => e.preventDefault(), false );

	unlock( dispatch( editSiteStore ) ).setCanvasMode( 'view' );
};

export const AssemblerHub: CustomizeStoreComponent = ( props ) => {
	const isInitializedRef = useRef( false );

	useBodyClass( 'woocommerce-assembler' );

	if ( ! isInitializedRef.current ) {
		initializeAssembleHub();
		isInitializedRef.current = true;
	}

	const [ showExitModal, setShowExitModal ] = useState( false );
	useEffect( () => {
		onBackButtonClicked( () => setShowExitModal( true ) );
	}, [] );
	// @ts-expect-error temp fix
	// Since we load the assember hub in an iframe, we don't have access to
	// xstate's context values.
	// This is the best workaround I can think of for now.
	// Set the aiOnline value from the parent window so that any child components
	// can access it.
	props.context.aiOnline = window.parent?.window.cys_aiOnline;

	return (
		<>
			{ showExitModal && (
				<GoBackWarningModal
					setOpenWarningModal={ setShowExitModal }
					onExitClicked={ () => {
						window.location.href = getNewPath( {}, '/', {} );
					} }
				/>
			) }
			<CustomizeStoreContext.Provider value={ props }>
				<ShortcutProvider style={ { height: '100%' } }>
					<GlobalStylesProvider>
						<RouterProvider>
							<Layout />
						</RouterProvider>
						<PreloadFonts />
					</GlobalStylesProvider>
				</ShortcutProvider>
			</CustomizeStoreContext.Provider>
		</>
	);
};
