/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { BlockEditProps } from '@wordpress/blocks';
import { useProductDataContext } from '@woocommerce/shared-context';

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
	const productType =
		product.id === 0 ? currentProductType?.slug : product.type;

	const blockProps = useBlockProps( {
		className,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: ATTRIBUTE_ITEM_TEMPLATE,
		templateLock: 'all',
	} );

	// If a valid product has been provided but it's not a
	// variable product, then don't render anything.
	if ( product.id !== 0 && productType !== 'variable' ) {
		return null;
	}

	return <div { ...innerBlocksProps } />;
}
