/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { store as blockEditorStore } from '@wordpress/block-editor';

const unsupportedBlocks = [
	'core/post-content',
	'woocommerce/mini-cart',
	'woocommerce/featured-product',
];
const supportedPrefixes = [ 'core/', 'woocommerce/' ];

const isBlockSupported = ( blockName: string ) => {
	// Check for explicitly unsupported blocks
	if ( unsupportedBlocks.includes( blockName ) ) {
		return false;
	}

	// Check for supported prefixes
	if (
		supportedPrefixes.find( ( prefix ) => blockName.startsWith( prefix ) )
	) {
		return true;
	}

	// Otherwise block is unsupported
	return false;
};

export const useHasUnsupportedBlocks = ( clientId: string ): boolean =>
	useSelect(
		( select ) => {
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore No types for this exist yet
			const { getClientIdsOfDescendants, getBlockName } =
				select( blockEditorStore );

			const hasUnsupportedBlocks =
				getClientIdsOfDescendants( clientId ).find(
					( blockId: string ) => {
						const blockName = getBlockName( blockId );
						const supported = isBlockSupported( blockName );
						return ! supported;
					}
				) || false;

			return hasUnsupportedBlocks;
		},
		[ clientId ]
	);
