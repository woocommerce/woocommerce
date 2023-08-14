// Ref: https://github.com/WordPress/gutenberg/blob/release/16.0/packages/edit-site/src/index.js

/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
import { GlobalStylesProvider } from '@wordpress/edit-site/build-module/components/global-styles/global-styles-provider';
import { privateApis as routerPrivateApis } from '@wordpress/router';
import { unlock } from '@wordpress/edit-site/build-module/private-apis';
import { dispatch } from '@wordpress/data';
import {
	registerCoreBlocks,
	__experimentalGetCoreBlocks,
} from '@wordpress/block-library';
import { getBlockType, store as blocksStore } from '@wordpress/blocks';
import { store as editSiteStore } from '@wordpress/edit-site/build-module/store';
import {
	__experimentalFetchLinkSuggestions as fetchLinkSuggestions,
	__experimentalFetchUrlData as fetchUrlData,
} from '@wordpress/core-data';
import { ShortcutProvider } from '@wordpress/keyboard-shortcuts';
import { store as preferencesStore } from '@wordpress/preferences';
import { store as editorStore } from '@wordpress/editor';

const { RouterProvider } = unlock( routerPrivateApis );

/**
 * Internal dependencies
 */
import './style.scss';
import { Layout } from './layout';

const StoreCustomize = () => {
	useEffect( () => {
		if ( ! window.blockSettings ) {
			// eslint-disable-next-line no-console
			console.warn(
				'window.blockSettings not found. Skipping initialization.'
			);
			return;
		}

		// Set up the block editor settings.
		const settings = window.blockSettings;
		settings.__experimentalFetchLinkSuggestions = (
			search,
			searchOptions
		) => fetchLinkSuggestions( search, searchOptions, settings );
		settings.__experimentalFetchRichUrlData = fetchUrlData;

		dispatch( blocksStore ).__experimentalReapplyBlockTypeFilters();
		const coreBlocks = __experimentalGetCoreBlocks().filter(
			( { name } ) => name !== 'core/freeform' && ! getBlockType( name )
		);
		registerCoreBlocks( coreBlocks );
		dispatch( blocksStore ).setFreeformFallbackBlockName( 'core/html' );

		dispatch( preferencesStore ).setDefaults( 'core/edit-site', {
			editorMode: 'visual',
			fixedToolbar: false,
			focusMode: false,
			keepCaretInsideBlock: false,
			welcomeGuide: true,
			welcomeGuideStyles: true,
			showListViewByDefault: false,
			showBlockBreadcrumbs: true,
		} );
		dispatch( editSiteStore ).updateSettings( settings );

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
	}, [] );

	useEffect( () => {
		document.body.classList.remove( 'woocommerce-admin-is-loading' );
		document.body.classList.add( 'woocommerce-profile-wizard__body' );
		document.body.classList.add( 'woocommerce-admin-full-screen' );
		document.body.classList.add( 'is-wp-toolbar-disabled' );

		return () => {
			document.body.classList.remove(
				'woocommerce-profile-wizard__body'
			);
			document.body.classList.remove( 'woocommerce-admin-full-screen' );
			document.body.classList.remove( 'is-wp-toolbar-disabled' );
		};
	}, [] );

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

export default StoreCustomize;
