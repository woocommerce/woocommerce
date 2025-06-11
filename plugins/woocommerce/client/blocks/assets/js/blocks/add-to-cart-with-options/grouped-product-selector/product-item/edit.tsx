/**
 * External dependencies
 */
import { useState, useEffect } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	__experimentalUseBlockPreview as useBlockPreview,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { BlockInstance, type BlockEditProps } from '@wordpress/blocks';
import { withProduct } from '@woocommerce/block-hocs';
import {
	InnerBlockLayoutContextProvider,
	ProductDataContextProvider,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { resolveSelect, useSelect } from '@wordpress/data';
import type { ProductResponseItem } from '@woocommerce/types';
import { productsStore } from '@woocommerce/data';

interface Attributes {
	className?: string;
}

type ProductItemProps = {
	attributes: { productId: number };
	isLoading?: boolean;
	product?: ProductResponseItem;
	blocks: BlockInstance[];
	isSelected: boolean;
	onSelect(): void;
};

const ProductItem = withProduct( function ProductItem( {
	attributes,
	isLoading,
	product,
	blocks,
	isSelected,
	onSelect,
}: ProductItemProps ) {
	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ role: 'listitem' },
		{ templateLock: 'insert' }
	);

	return (
		<BlockContextProvider
			value={ { postId: attributes.productId, postType: 'product' } }
		>
			<ProductDataContextProvider
				product={ product as ProductResponseItem }
				isLoading={ isLoading as boolean }
			>
				{ isSelected ? <div { ...innerBlocksProps } /> : <></> }

				<div
					role="listitem"
					style={ { display: isSelected ? 'none' : undefined } }
				>
					<div
						{ ...blockPreviewProps }
						role="button"
						tabIndex={ 0 }
						onClick={ onSelect }
						onKeyDown={ onSelect }
					/>
				</div>
			</ProductDataContextProvider>
		</BlockContextProvider>
	);
} );

export default function ProductItemTemplateEdit(
	props: BlockEditProps< Attributes >
) {
	const { clientId } = props;
	const { className } = props.attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	const { product } = useProductDataContext();
	const [ products, setProducts ] = useState< ProductResponseItem[] | null >(
		null
	);

	useEffect( () => {
		const fetchChildProducts = async ( groupedProductIds: number[] ) => {
			if ( ! groupedProductIds || groupedProductIds.length === 0 ) {
				return;
			}

			resolveSelect( productsStore )
				.getProducts( {
					include: groupedProductIds,
					per_page: groupedProductIds.length,
					_fields: [ 'id' ],
				} )
				.then( ( fetchedProducts ) => {
					setProducts( fetchedProducts );
				} );
		};

		if ( ! products ) {
			if ( product.id !== 0 && product.type === 'grouped' ) {
				fetchChildProducts( product.grouped_products );
			} else if ( product.id === 0 ) {
				// If product ID is 0, then we must be editing a template.
				// Fetch an existing grouped product so template can be edited.
				resolveSelect( productsStore )
					.getProducts( { type: 'grouped', per_page: 1 } )
					.then( ( groupedProduct ) => {
						if ( groupedProduct.length > 0 ) {
							fetchChildProducts(
								groupedProduct[ 0 ].grouped_products
							);
						} else {
							// If there are no grouped products, query for any three other products.
							resolveSelect( productsStore )
								.getProducts( {
									per_page: 3,
									_fields: [ 'id' ],
								} )
								.then( ( fetchedProducts ) => {
									if ( fetchedProducts.length > 0 ) {
										setProducts( fetchedProducts );
									}
								} );
						}
					} );
			}
		}
	}, [ products, product ] );

	const { blocks } = useSelect(
		( select ) => {
			const { getBlocks } = select( blockEditorStore );
			return { blocks: getBlocks( clientId ) };
		},
		[ clientId ]
	);

	const [ selectedProductItem, setSelectedProductItem ] =
		useState< number >();

	return (
		<div { ...blockProps }>
			<InnerBlockLayoutContextProvider parentName="woocommerce/add-to-cart-with-options-grouped-product-item">
				<div role="list">
					{ products?.map( ( productItem ) => (
						<ProductItem
							key={ productItem.id }
							attributes={ {
								productId: productItem.id,
							} }
							blocks={ blocks }
							isSelected={
								( selectedProductItem || products[ 0 ]?.id ) ===
								productItem.id
							}
							onSelect={ () =>
								setSelectedProductItem( productItem.id )
							}
						/>
					) ) }
				</div>
			</InnerBlockLayoutContextProvider>
		</div>
	);
}
