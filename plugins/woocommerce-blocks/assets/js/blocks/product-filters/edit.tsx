/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import type { ProductFiltersBlockAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 3,
			style: { typography: { fontSize: '24px' } },
			content: __( 'Filters', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'active-filters',
			heading: __( 'Active', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'price-filter',
			heading: __( 'Price', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'stock-filter',
			heading: __( 'Status', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'rating-filter',
			heading: __( 'Rating', 'woocommerce' ),
		},
	],
];

export const Edit = ( {}: BlockEditProps< ProductFiltersBlockAttributes > ) => {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps }>
			<InnerBlocks templateLock={ false } template={ TEMPLATE } />
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
