/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import { store as coreStore } from '@wordpress/core-data';
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
import { useSelect } from '@wordpress/data';
import type { ProductResponseItem } from '@woocommerce/types';

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

	const { products } = useSelect(
		( select ) => {
			const postType = 'product';
			const { getEntityRecords } = select( coreStore );

			let query: Record< string, unknown > = {
				per_page: 3,
			};

			const groupedProductIds = product.grouped_products ?? [];
			if ( groupedProductIds.length ) {
				query = { include: groupedProductIds };
			}

			return {
				products:
					getEntityRecords< ProductResponseItem >(
						'postType',
						postType,
						query
					) ?? [],
			};
		},
		[ product ]
	);

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
					{ products.map( ( productItem ) => (
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
