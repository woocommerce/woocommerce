/**
 * External dependencies
 */
import { select } from '@wordpress/data';

export function useBlocksHelper() {
	function getClosestParentTabId( clientId: string ) {
		const [ closestParentClientId ] =
			// @ts-expect-error Outdated type definition.
			select( 'core/block-editor' ).getBlockParentsByBlockName(
				clientId,
				'woocommerce/product-tab',
				true
			);
		if ( ! closestParentClientId ) {
			return '';
		}
		// @ts-expect-error Outdated type definition.
		const { attributes } = select( 'core/block-editor' ).getBlock(
			closestParentClientId
		);
		return attributes?.id;
	}

	function getParentTabId( clientId?: string ) {
		if ( clientId ) {
			return getClosestParentTabId( clientId );
		}

		const skuClientIds =
			// @ts-expect-error Outdated type definition.
			select( 'core/block-editor' ).getBlocksByName(
				'woocommerce/product-sku-field'
			);

		console.log( 'skuClientIds', skuClientIds );
		if ( skuClientIds.length ) {
			return getClosestParentTabId( skuClientIds[ 0 ] );
		}
		return 'inventory';
	}

	// function getParentTab( clientId: string ) {
	// 	// const skuClientIds = wp.data.select( wp.blockEditor.store ).getBlocksByName('woocommerce/product-sku-field');
	// 	// errorContext = skuClientIds.length ? getParentTabId( skuClientIds[0] ) : 'inventory';
	// 	const skuClientIds =
	// 		// @ts-expect-error Outdated type definition.
	// 		select( 'core/block-editor' ).getBlocksByName(
	// 			'woocommerce/product-sku-field'
	// 		);
	// 	const errorContext = skuClientIds.length
	// 		? getParentTabId( skuClientIds[ 0 ] )
	// 		: 'inventory';
	// 	console.log( 'errorContext', errorContext );
	// 	return errorContext;
	// 	// if ( ! closestParentClientId ) {
	// 	// 	return '';
	// 	// }
	// }

	return {
		getParentTabId,
	};
}
