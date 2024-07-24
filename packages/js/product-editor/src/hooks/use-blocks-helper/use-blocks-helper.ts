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

		if ( skuClientIds.length ) {
			return getClosestParentTabId( skuClientIds[ 0 ] );
		}
		return 'inventory';
	}

	return {
		getParentTabId,
	};
}
