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
import { isProductResponseItem } from '@woocommerce/entities';
import { Spinner } from '@wordpress/components';
import { previewProductResponseItems } from '@woocommerce/resource-previews';

interface Attributes {
	className?: string;
}

type ProductItemWithContextProps = {
	attributes: { productId: number };
	isLoading?: boolean;
	product?: ProductResponseItem | null;
	blocks: BlockInstance[];
	isSelected: boolean;
	onSelect(): void;
};

type ProductItemProps = {
	product: ProductResponseItem | null;
	blocks: BlockInstance[];
	isLoading: boolean;
	isSelected: boolean;
	onSelect(): void;
};

const ProductItem = ( {
	product,
	blocks,
	isLoading,
	isSelected,
	onSelect,
}: ProductItemProps ) => {
	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{},
		{ templateLock: 'insert' }
	);

	return (
		<ProductDataContextProvider product={ product } isLoading={ isLoading }>
			{ isSelected ? (
				<div { ...innerBlocksProps } />
			) : (
				// We don't need this element to be interactive with the
				// keyboard because the first child row is always editable.
				// We allow clicking on the blocks of other children rows
				// but it's not critical, so we disable the keyboard events.
				// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
				<div { ...blockPreviewProps } onClick={ onSelect } />
			) }
		</ProductDataContextProvider>
	);
};

const ProductItemWithContext = withProduct(
	( {
		attributes,
		isLoading = true,
		product = null,
		blocks,
		isSelected,
		onSelect,
	}: ProductItemWithContextProps ) => {
		return (
			<BlockContextProvider
				value={ { postId: attributes.productId, postType: 'product' } }
			>
				<ProductItem
					product={ product }
					blocks={ blocks }
					isLoading={ isLoading }
					isSelected={ isSelected }
					onSelect={ onSelect }
				/>
			</BlockContextProvider>
		);
	}
);

export default function ProductItemTemplateEdit(
	props: BlockEditProps< Attributes >
) {
	const { clientId } = props;
	const { className } = props.attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	const { product, isLoading } = useProductDataContext();
	const [ isLoadingProducts, setIsLoadingProducts ] = useState( true );
	const [ products, setProducts ] = useState< ProductResponseItem[] | null >(
		null
	);
	const productsLength = products?.length || 0;

	useEffect( () => {
		const fetchChildProducts = async ( groupedProductIds: number[] ) => {
			if ( ! groupedProductIds || groupedProductIds.length === 0 ) {
				setIsLoadingProducts( false );
				return;
			}

			void resolveSelect( productsStore )
				.getProducts( {
					include: groupedProductIds,
					per_page: groupedProductIds.length,
					_fields: [ 'id' ],
				} )
				.then( ( fetchedProducts ) => {
					setProducts( fetchedProducts );
					setIsLoadingProducts( false );
				} );
		};

		if ( ! isLoading && product && productsLength === 0 ) {
			if ( isProductResponseItem( product ) ) {
				void fetchChildProducts( product.grouped_products );
			} else {
				// If not editing a specific product, we are editing a template.
				// Fetch an existing grouped product so template can be edited.
				void resolveSelect( productsStore )
					.getProducts( { type: 'grouped', per_page: 1 } )
					.then( ( groupedProduct ) => {
						if (
							groupedProduct.length > 0 &&
							groupedProduct[ 0 ]?.grouped_products?.length > 0
						) {
							void fetchChildProducts(
								groupedProduct[ 0 ].grouped_products
							);
						} else {
							// If there are no grouped products, query for any three other products.
							void resolveSelect( productsStore )
								.getProducts( {
									per_page: 3,
									_fields: [ 'id' ],
								} )
								.then( ( fetchedProducts ) => {
									if ( fetchedProducts.length > 0 ) {
										setProducts( fetchedProducts );
									}
									setIsLoadingProducts( false );
								} );
						}
					} );
			}
		}
	}, [ isLoading, product, productsLength ] );

	const { blocks } = useSelect(
		( select ) => {
			const { getBlocks } = select( blockEditorStore );
			return { blocks: getBlocks( clientId ) };
		},
		[ clientId ]
	);

	const [ selectedProductItem, setSelectedProductItem ] =
		useState< number >();

	if ( isLoading || isLoadingProducts ) {
		return <Spinner />;
	}

	const productList = products
		? products?.map( ( productItem ) => (
				<ProductItemWithContext
					key={ productItem.id }
					attributes={ {
						productId: productItem.id,
					} }
					blocks={ blocks }
					isSelected={
						( selectedProductItem ?? products[ 0 ]?.id ) ===
						productItem.id
					}
					onSelect={ () => setSelectedProductItem( productItem.id ) }
				/>
		  ) )
		: previewProductResponseItems?.map( ( productItem ) => (
				<ProductItem
					key={ productItem.id }
					product={ productItem }
					blocks={ blocks }
					isLoading={ false }
					isSelected={
						( selectedProductItem ??
							previewProductResponseItems[ 0 ]?.id ) ===
						productItem.id
					}
					onSelect={ () => setSelectedProductItem( productItem.id ) }
				/>
		  ) );

	return (
		<div { ...blockProps }>
			<InnerBlockLayoutContextProvider parentName="woocommerce/add-to-cart-with-options-grouped-product-item">
				{ productList }
			</InnerBlockLayoutContextProvider>
		</div>
	);
}
