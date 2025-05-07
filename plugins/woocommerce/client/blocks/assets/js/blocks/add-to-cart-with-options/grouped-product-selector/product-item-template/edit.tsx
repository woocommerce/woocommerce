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
import { store as coreStore } from '@wordpress/core-data';

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
	const [ groupedProducts, setGroupedProducts ] = useState<
		ProductResponseItem[] | null
	>( null );

	useEffect( () => {
		const fetchGroupedProducts = async (
			productContext: ProductResponseItem[]
		) => {
			if ( ! productContext || productContext.length === 0 ) {
				return;
			}

			let query: Record< string, unknown > = { per_page: 3 };
			let groupedProductIds: number[] = [];

			// If there are multiple products, use these as the grouped products.
			if ( productContext.length > 1 ) {
				groupedProductIds = productContext.map( ( prod ) => prod.id );
			}

			// If there is a single product, use the grouped products from the product.
			if ( productContext.length === 1 ) {
				groupedProductIds = productContext[ 0 ].grouped_products;
				query.per_page = productContext[ 0 ].grouped_products.length;
			}

			if ( groupedProductIds.length ) {
				query = { ...query, include: groupedProductIds };
			}

			resolveSelect( coreStore )
				.getEntityRecords( 'postType', 'product', query )
				.then( ( products ) => {
					setGroupedProducts( products as ProductResponseItem[] );
				} );
		};

		if ( ! groupedProducts ) {
			if ( product.id !== 0 && product.type === 'grouped' ) {
				fetchGroupedProducts( [ product ] );
			} else if ( product.id === 0 ) {
				// If product ID is 0, then we must be editing a template.
				// Fetch an existing grouped product so template can be edited.
				resolveSelect( productsStore )
					.getProducts( { type: 'grouped', per_page: 1 } )
					.then( ( fetchedGroupedProduct ) => {
						if ( fetchedGroupedProduct.length > 0 ) {
							fetchGroupedProducts( fetchedGroupedProduct );
						} else {
							// If there are no grouped products, query for any three other products.
							resolveSelect( productsStore )
								.getProducts( { per_page: 3, order: 'desc' } )
								.then( ( fetchedProducts ) => {
									if ( fetchedProducts.length > 0 ) {
										fetchGroupedProducts( fetchedProducts );
									}
								} );
						}
					} );
			}
		}
	}, [ groupedProducts, product ] );

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
			<InnerBlockLayoutContextProvider parentName="woocommerce/add-to-cart-with-options-grouped-product-selector-item">
				<div role="list">
					{ groupedProducts?.map( ( productItem ) => (
						<ProductItem
							key={ productItem.id }
							attributes={ {
								productId: productItem.id,
							} }
							blocks={ blocks }
							isSelected={
								( selectedProductItem ||
									groupedProducts[ 0 ]?.id ) ===
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
