/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useProductDataContext } from '@woocommerce/shared-context';

/**
 * Internal dependencies
 */
import useProductTypeSelector from '../hooks/use-product-type-selector';
import { ATTRIBUTE_ITEM_TEMPLATE } from './attribute-item-template/constants';

interface Attributes {
	className?: string;
}

export default function AddToCartWithOptionsVariationSelectorEdit(
	props: BlockEditProps< Attributes >
) {
	const { className } = props.attributes;
	const { current: currentProductType } = useProductTypeSelector();
	const { product } = useProductDataContext();
	const productType =
		product.id === 0 ? currentProductType?.slug : product.type;

	const blockProps = useBlockProps( {
		className,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: ATTRIBUTE_ITEM_TEMPLATE,
		templateLock: 'all',
	} );

	if ( productType !== 'variable' ) {
		return null;
	}

	return <div { ...innerBlocksProps } />;
}
