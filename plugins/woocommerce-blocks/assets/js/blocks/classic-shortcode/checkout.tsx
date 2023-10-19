/**
 * External dependencies
 */
import { createBlock, type BlockInstance } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { OnClickCallbackParameter, InheritedAttributes } from './types';

const isConversionPossible = () => {
	return true;
};

const getButtonLabel = () =>
	__( 'Transform into blocks', 'woo-gutenberg-products-block' );

const getBlockifiedTemplate = ( inheritedAttributes: InheritedAttributes ) =>
	[
		createBlock( 'woocommerce/checkout', {
			...inheritedAttributes,
			className: 'wc-block-checkout',
		} ),
	].filter( Boolean ) as BlockInstance[];

const onClickCallback = ( {
	clientId,
	attributes,
	getBlocks,
	replaceBlock,
	selectBlock,
}: OnClickCallbackParameter ) => {
	replaceBlock( clientId, getBlockifiedTemplate( attributes ) );

	const blocks = getBlocks();

	const groupBlock = blocks.find(
		( block ) =>
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock ) =>
					innerBlock.name === 'woocommerce/store-notices'
			)
	);

	if ( groupBlock ) {
		selectBlock( groupBlock.clientId );
	}
};

const getTitle = () => {
	return __( 'Classic Checkout', 'woo-gutenberg-products-block' );
};

const getDescription = () => {
	return __(
		'This block will render the classic checkout shortcode. You can optionally transform it into blocks for more control over the checkout experience.',
		'woo-gutenberg-products-block'
	);
};

const blockifyConfig = {
	getButtonLabel,
	onClickCallback,
	getBlockifiedTemplate,
};

export { blockifyConfig, isConversionPossible, getDescription, getTitle };
