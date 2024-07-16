/**
 * External dependencies
 */
import { select } from '@wordpress/data';

export function useBlocksHelper() {
	function getParentTabId( clientId: string ) {
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

	return {
		getParentTabId,
	};
}
