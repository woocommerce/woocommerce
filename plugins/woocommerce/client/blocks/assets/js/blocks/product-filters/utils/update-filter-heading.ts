/**
 * External dependencies
 */
import { dispatch, select } from '@wordpress/data';
import { getInnerBlockByName } from '@woocommerce/utils';

/**
 * Updates the content of a filter heading block within a given filter block.
 *
 * @param clientId - The client ID of the filter block containing the heading block
 * @param content  - The new content for the heading block
 */
export const updateFilterHeading = (
	clientId: string,
	content: string
): void => {
	const { getBlock } = select( 'core/block-editor' );
	const filterBlock = getBlock( clientId );

	if ( ! filterBlock ) {
		return;
	}

	const { updateBlockAttributes } = dispatch( 'core/block-editor' );
	const filterHeadingBlock = getInnerBlockByName(
		filterBlock,
		'core/heading'
	);

	if ( filterHeadingBlock ) {
		updateBlockAttributes( filterHeadingBlock.clientId, {
			content,
		} );
	}
};
