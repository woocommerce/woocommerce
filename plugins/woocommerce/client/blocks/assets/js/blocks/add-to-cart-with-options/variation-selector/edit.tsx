/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useProductDataContext } from '@woocommerce/shared-context';
import { isProductResponseItem } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import { useProductTypeSelector } from '../../../shared/stores/product-type-template-state';
import { ATTRIBUTE_ITEM_TEMPLATE } from './attribute/constants';

interface Attributes {
	className?: string;
}

export default function AddToCartWithOptionsVariationSelectorEdit(
	props: BlockEditProps< Attributes >
) {
	const { className } = props.attributes;
	const { current: currentProductType } = useProductTypeSelector();
	const { product } = useProductDataContext();

	const blockProps = useBlockProps( {
		className,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: ATTRIBUTE_ITEM_TEMPLATE,
		templateLock: 'all',
	} );

	const productType = ! isProductResponseItem( product )
		? currentProductType?.slug
		: product.type;

	if ( productType !== 'variable' ) {
		return null;
	}

	return <div { ...innerBlocksProps } />;
}
