/**
 * Internal dependencies
 */
import { updateBlockSettings } from '../../config-tools/block-config';

/**
 * Remove the styles and alignment control support for the Quote block.
 */
function enhanceQuoteBlock() {
	updateBlockSettings( 'core/quote', ( current ) => ( {
		...current,
		styles: [],
		supports: {
			...current.supports,
			align: [],
		},
	} ) );
}

export { enhanceQuoteBlock };
