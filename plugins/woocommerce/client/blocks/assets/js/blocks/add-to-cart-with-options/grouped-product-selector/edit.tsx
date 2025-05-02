/**
 * External dependencies
 */
import { useProductDataContext } from '@woocommerce/shared-context';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { type BlockEditProps } from '@wordpress/blocks';
import { resolveSelect } from '@wordpress/data';
import { productsStore } from '@woocommerce/data';
import { useEffect, useState } from '@wordpress/element';
import type { ProductResponseItem } from '@woocommerce/types';
import { __ } from '@wordpress/i18n';
import NoticeBanner from '@woocommerce/base-components/notice-banner';

/**
 * Internal dependencies
 */
import { GROUPED_PRODUCT_ITEM_TEMPLATE } from './product-item-template/constants';

interface Attributes {
	className?: string;
}

export default function AddToCartWithOptionsGroupedProductSelectorEdit(
	props: BlockEditProps< Attributes >
) {
	const { className } = props.attributes;
	const { product } = useProductDataContext();
	const blockProps = useBlockProps( {
		className,
	} );
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: GROUPED_PRODUCT_ITEM_TEMPLATE,
		templateLock: 'all',
	} );
	const [ groupedProduct, setGroupedProduct ] = useState<
		ProductResponseItem[]
	>( [] );

	// If product ID is 0, then we must be editing a template.
	// Fetch an existing grouped product so template can be edited.
	useEffect( () => {
		if ( product.id !== 0 && product.type === 'grouped' ) {
			setGroupedProduct( [ product as ProductResponseItem ] );
		}
		if ( groupedProduct.length === 0 && product.id === 0 ) {
			resolveSelect( productsStore )
				.getProducts( {
					type: 'grouped',
					per_page: 1,
				} )
				.then( ( fetchedProduct ) => {
					if ( fetchedProduct.length > 0 ) {
						setGroupedProduct( fetchedProduct );
					}
				} );
		}
	}, [ groupedProduct, product ] );

	if ( groupedProduct.length === 0 ) {
		return (
			<div { ...innerBlocksProps }>
				<NoticeBanner status="warning">
					{ __(
						'No grouped products were found. Please create a grouped product first.',
						'woocommerce'
					) }
				</NoticeBanner>
			</div>
		);
	}

	return <div { ...innerBlocksProps } />;
}
