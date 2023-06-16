// ref: https://github.com/WordPress/gutenberg/blob/trunk/packages/edit-site/src/index.js

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

const { RouterProvider } = unlock( routerPrivateApis );

/**
 * Internal dependencies
 */
import './style.scss';
import { Layout } from './layout';

const StoreCustomize = () => {
	useEffect( () => {
		// Register core blocks and set up the fallback block.
		dispatch( blocksStore ).__experimentalReapplyBlockTypeFilters();

		const coreBlocks = __experimentalGetCoreBlocks().filter(
			( { name } ) => name !== 'core/freeform' && ! getBlockType( name )
		);

		registerCoreBlocks( coreBlocks );
		dispatch( blocksStore ).setFreeformFallbackBlockName( 'core/html' );

		// Set up the block editor settings.
		const settings = window.blockSettings;
		settings.__experimentalFetchLinkSuggestions = (
			search,
			searchOptions
		) => fetchLinkSuggestions( search, searchOptions, settings );
		settings.__experimentalFetchRichUrlData = fetchUrlData;

		dispatch( editSiteStore ).updateSettings( settings );
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
		<GlobalStylesProvider>
			<RouterProvider>
				<Layout />
			</RouterProvider>
		</GlobalStylesProvider>
	);
};

export default StoreCustomize;
