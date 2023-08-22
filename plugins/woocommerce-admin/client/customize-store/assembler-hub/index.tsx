// Reference: https://github.com/WordPress/gutenberg/tree/v16.4.0/packages/edit-site/src/index.js
/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { dispatch, useDispatch } from '@wordpress/data';
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
// @ts-ignore No types for this exist yet.
import { getBlockType, store as blocksStore } from '@wordpress/blocks';
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

/**
 * Internal dependencies
 */
import { CustomizeStoreComponent } from '../types';
import { Layout } from './layout';
import './style.scss';

const { RouterProvider } = unlock( routerPrivateApis );

export type events = { type: 'FINISH_CUSTOMIZATION' };

export const AssemblerHub: CustomizeStoreComponent = () => {
	const { setCanvasMode } = unlock( useDispatch( editSiteStore ) );

	useEffect( () => {
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

		// @ts-ignore No types for this exist yet.
		dispatch( blocksStore ).__experimentalReapplyBlockTypeFilters();
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
		window.addEventListener(
			'dragover',
			( e ) => e.preventDefault(),
			false
		);
		window.addEventListener( 'drop', ( e ) => e.preventDefault(), false );

		setCanvasMode( 'view' );
	}, [ setCanvasMode ] );

	return (
		<ShortcutProvider style={ { height: '100%' } }>
			<GlobalStylesProvider>
				<RouterProvider>
					<Layout />
				</RouterProvider>
			</GlobalStylesProvider>
		</ShortcutProvider>
	);
};
