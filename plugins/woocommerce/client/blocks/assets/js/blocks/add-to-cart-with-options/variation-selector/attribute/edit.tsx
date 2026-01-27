/**
 * External dependencies
 */
import { useState } from '@wordpress/element';
import {
	useBlockProps,
	useInnerBlocksProps,
	store as blockEditorStore,
	__experimentalUseBlockPreview as useBlockPreview,
} from '@wordpress/block-editor';
import { BlockInstance, type BlockEditProps } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import {
	CustomDataProvider,
	useProductDataContext,
} from '@woocommerce/shared-context';
import { isProductResponseItem } from '@woocommerce/entities';

/**
 * Internal dependencies
 */
import { DEFAULT_ATTRIBUTES } from './constants';

interface Attributes {
	className?: string;
}

type AttributeItemProps = {
	blocks: BlockInstance[];
	isSelected: boolean;
	onSelect(): void;
};

function AttributeItem( { blocks, isSelected, onSelect }: AttributeItemProps ) {
	const blockPreviewProps = useBlockPreview( {
		blocks,
	} );
	const innerBlocksProps = useInnerBlocksProps(
		{ role: 'listitem' },
		{ templateLock: 'insert' }
	);

	return (
		<>
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
		</>
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
					/>
				</CustomDataProvider>
			) ) }
		</div>
	);
}
