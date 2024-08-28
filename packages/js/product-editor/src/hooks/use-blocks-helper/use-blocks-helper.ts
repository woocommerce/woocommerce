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
			return null;
		}
		// @ts-expect-error Outdated type definition.
		const { attributes } = select( 'core/block-editor' ).getBlock(
			closestParentClientId
		);
		return attributes?.id;
	}

	function getClientIdByField( field: HTMLElement ) {
		const parentBlockElement = field.closest(
			'[data-block]'
		) as HTMLElement;
		return parentBlockElement?.dataset.block;
	}

	function getParentTabId( clientId?: string | null ) {
		if ( clientId ) {
			return getClosestParentTabId( clientId );
		}
		return null;
	}

	function getParentTabIdByBlockName( blockName: string ) {
		const blockClientIds =
			// @ts-expect-error Outdated type definition.
			select( 'core/block-editor' ).getBlocksByName( blockName );

		if ( blockClientIds.length ) {
			return getClosestParentTabId( blockClientIds[ 0 ] );
		}
		return null;
	}

	return {
		getClientIdByField,
		getParentTabId,
		getParentTabIdByBlockName,
	};
}
