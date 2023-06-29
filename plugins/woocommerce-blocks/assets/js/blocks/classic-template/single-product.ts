/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';
import { isWpVersion } from '@woocommerce/settings';
import { BlockInstance, createBlock } from '@wordpress/blocks';
import { VARIATION_NAME as PRODUCT_TITLE_VARIATION_NAME } from '@woocommerce/blocks/product-query/variations/elements/product-title';
import { VARIATION_NAME as PRODUCT_SUMMARY_VARIATION_NAME } from '@woocommerce/blocks/product-query/variations/elements/product-summary';

/**
 * Internal dependencies
 */
import { OnClickCallbackParameter } from './types';

const getBlockifiedTemplate = () =>
	[
		createBlock( 'woocommerce/breadcrumbs' ),
		createBlock( 'woocommerce/store-notices' ),
		createBlock(
			'core/columns',
			{
				align: 'wide',
			},
			[
				createBlock(
					'core/column',
					{
						type: 'constrained',
						justifyContent: 'right',
						width: '512px',
					},
					[ createBlock( 'woocommerce/product-image-gallery' ) ]
				),
				createBlock( 'core/column', {}, [
					createBlock( 'core/post-title', {
						__woocommerceNamespace: PRODUCT_TITLE_VARIATION_NAME,
						level: 1,
					} ),
					createBlock( 'woocommerce/product-rating' ),
					createBlock( 'woocommerce/product-price', {
						fontSize: 'large',
					} ),
					createBlock( 'core/post-excerpt', {
						__woocommerceNamespace: PRODUCT_SUMMARY_VARIATION_NAME,
					} ),
					createBlock( 'woocommerce/add-to-cart-form' ),
					createBlock( 'woocommerce/product-meta' ),
				] ),
			]
		),
		createBlock( 'woocommerce/product-details', {
			align: 'wide',
		} ),
		createBlock( 'woocommerce/related-products', {
			align: 'wide',
		} ),
	].filter( Boolean ) as BlockInstance[];

const isConversionPossible = () => {
	// Blockification is possible for the WP version 6.1 and above,
	// which are the versions the Products block supports.
	return isWpVersion( '6.1', '>=' );
};

const getDescriptionAllowingConversion = ( templateTitle: string ) =>
	sprintf(
		/* translators: %s is the template title */
		__(
			'Transform this template into multiple blocks so you can add, remove, reorder, and customize your %s template.',
			'woo-gutenberg-products-block'
		),
		templateTitle
	);

const getDescriptionDisallowingConversion = ( templateTitle: string ) =>
	sprintf(
		/* translators: %s is the template title */
		__(
			'This block serves as a placeholder for your %s. It will display the actual product image, title, price in your store. You can move this placeholder around and add more blocks around to customize the template.',
			'woo-gutenberg-products-block'
		),
		templateTitle
	);

const getDescription = ( templateTitle: string, canConvert: boolean ) => {
	if ( canConvert ) {
		return getDescriptionAllowingConversion( templateTitle );
	}

	return getDescriptionDisallowingConversion( templateTitle );
};

const getButtonLabel = () =>
	__( 'Transform into blocks', 'woo-gutenberg-products-block' );

const onClickCallback = ( {
	clientId,
	getBlocks,
	replaceBlock,
	selectBlock,
}: OnClickCallbackParameter ) => {
	replaceBlock( clientId, getBlockifiedTemplate() );

	const blocks = getBlocks();
	const groupBlock = blocks.find(
		( block ) =>
			block.name === 'core/group' &&
			block.innerBlocks.some(
				( innerBlock ) => innerBlock.name === 'woocommerce/breadcrumbs'
			)
	);

	if ( groupBlock ) {
		selectBlock( groupBlock.clientId );
	}
};

const blockifyConfig = {
	getButtonLabel,
	onClickCallback,
	getBlockifiedTemplate,
};

export { isConversionPossible, getDescription, blockifyConfig };
