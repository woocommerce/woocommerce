/**
 * External dependencies
 */
import { useState, useMemo } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	__experimentalUseBlockPreview as useBlockPreview,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { BlockInstance, type BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import {
	CustomDataProvider,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isProductResponseItem } from '@woocommerce/entities';
import type { ProductResponseAttributeItem } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { DEFAULT_ATTRIBUTES } from './constants';
import type { SelectableItemsContext } from '../../../../types/type-defs/selectable-items';

interface Attributes {
	className?: string;
}

type AttributeItemProps = {
	blocks: BlockInstance[];
	isSelected: boolean;
	onSelect(): void;
	attribute: ProductResponseAttributeItem;
};

function AttributeItem( {
	blocks,
	isSelected,
	onSelect,
	attribute,
}: AttributeItemProps ) {
	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ role: 'listitem' },
		{ templateLock: 'insert' }
	);

	const selectableItemsContext = useMemo< SelectableItemsContext >( () => {
		const items = attribute.terms.map( ( term, index ) => ( {
			id: `variation-attr-${ term.slug }`,
			label: term.name,
			value: term.slug,
			selected: index === 0,
		} ) );
		return {
			items,
			selectionMode: 'single' as const,
			storeNamespace: 'woocommerce/add-to-cart-with-options',
			groupLabel: attribute.name,
		};
	}, [ attribute ] );

	return (
		<BlockContextProvider
			value={ { 'woocommerce/selectableItems': selectableItemsContext } }
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
		</BlockContextProvider>
	);
}

export default function AttributeItemTemplateEdit(
	props: BlockEditProps< Attributes >
) {
	const { clientId } = props;
	const { className } = props.attributes;

	const blockProps = useBlockProps( {
		className,
	} );

	const { product } = useProductDataContext();

	const productAttributes =
		isProductResponseItem( product ) && product.type === 'variable'
			? product.attributes
			: DEFAULT_ATTRIBUTES;

	const { blocks } = useSelect(
		( select ) => {
			const { getBlocks } = select( blockEditorStore );
			return { blocks: getBlocks( clientId ) };
		},
		[ clientId ]
	);

	const [ selectedAttributeItem, setSelectedAttributeItem ] =
		useState< number >();

	return (
		<div { ...blockProps } role="list">
			{ productAttributes.map( ( attribute ) => (
				<CustomDataProvider
					key={ attribute.id }
					id="attribute"
					data={ attribute }
				>
					<AttributeItem
						blocks={ blocks }
						isSelected={
							( selectedAttributeItem ||
								productAttributes[ 0 ]?.id ) === attribute.id
						}
						onSelect={ () =>
							setSelectedAttributeItem( attribute.id )
						}
						attribute={ attribute }
					/>
				</CustomDataProvider>
			) ) }
		</div>
	);
}
