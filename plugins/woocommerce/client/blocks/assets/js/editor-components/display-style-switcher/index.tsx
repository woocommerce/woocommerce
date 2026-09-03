/**
 * External dependencies
 */
import {
	createBlock,
	getBlockTypes,
	type BlockInstance,
} from '@wordpress/blocks';
import { useState } from '@wordpress/element';
import { dispatch, select, useDispatch } from '@wordpress/data';
import { getInnerBlockByName } from '@woocommerce/utils';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

const SELECTABLE_ITEMS_CONTEXT = 'woocommerce/selectableItems';

type DisplayStyleInsertionPoint = {
	rootClientId: string;
	index: number;
};

type DisplayStyleBlockSupport = {
	woocommerce?: {
		innerBlockDisplayStyle?: unknown;
	};
};

/**
 * By default, the current parent block is the insertion point. For complex
 * block compositions, the default insertion point can be an inner block of
 * the parent, such as the Variation Attribute Selector block.
 */
type GetFallbackDisplayStyleInsertionPoint = (
	parentBlock: BlockInstance
) => DisplayStyleInsertionPoint;

type DisplayStyleBlockType = ReturnType< typeof getBlockTypes >[ number ] & {
	ancestor?: readonly string[] | string;
	usesContext?: readonly string[] | string;
	supports?: DisplayStyleBlockSupport;
};

type DisplayStyleSwitcherProps = {
	clientId: string;
	currentStyle: string;
	onChange: ( value: string ) => void;
	contextKey?: string;
	getFallbackDisplayStyleInsertionPoint?: GetFallbackDisplayStyleInsertionPoint;
};

function isBlockInstance(
	block: BlockInstance | null
): block is BlockInstance {
	return Boolean( block );
}

function getBlockTypeList(
	value: readonly string[] | string | undefined
): readonly string[] {
	if ( ! value ) {
		return [];
	}
	return Array.isArray( value ) ? value : [ value ];
}

function hasInnerBlockDisplayStyleSupport(
	blockType: DisplayStyleBlockType
): boolean {
	return blockType.supports?.woocommerce?.innerBlockDisplayStyle === true;
}

function isDisplayStyleCandidate(
	blockType: DisplayStyleBlockType,
	parentBlockName: string | undefined,
	contextKey: string
): boolean {
	if ( ! parentBlockName ) {
		return false;
	}

	if ( ! hasInnerBlockDisplayStyleSupport( blockType ) ) {
		return false;
	}

	return (
		getBlockTypeList( blockType.ancestor ).includes( parentBlockName ) &&
		getBlockTypeList( blockType.usesContext ).includes( contextKey )
	);
}

function getDisplayStyleOptions(
	parentBlockName: string | undefined,
	contextKey: string
): DisplayStyleBlockType[] {
	return ( getBlockTypes() as DisplayStyleBlockType[] ).filter(
		( blockType ) =>
			isDisplayStyleCandidate( blockType, parentBlockName, contextKey )
	);
}

function getCurrentDisplayStyleBlock(
	parentBlock: BlockInstance,
	displayStyleOptions: DisplayStyleBlockType[]
): BlockInstance | null {
	return (
		displayStyleOptions
			.map( ( blockType ) =>
				getInnerBlockByName( parentBlock, blockType.name )
			)
			.find( isBlockInstance ) ?? null
	);
}

function getDisplayStyleInsertionPoint(
	parentBlock: BlockInstance,
	getFallbackDisplayStyleInsertionPoint?: GetFallbackDisplayStyleInsertionPoint
): DisplayStyleInsertionPoint {
	return (
		getFallbackDisplayStyleInsertionPoint?.( parentBlock ) ?? {
			rootClientId: parentBlock.clientId,
			index: parentBlock.innerBlocks.length,
		}
	);
}

export const DisplayStyleSwitcher = ( {
	clientId,
	currentStyle,
	onChange,
	contextKey = SELECTABLE_ITEMS_CONTEXT,
	getFallbackDisplayStyleInsertionPoint,
}: DisplayStyleSwitcherProps ) => {
	const parentBlock = select( 'core/block-editor' ).getBlock( clientId );
	const parentBlockName = parentBlock?.name;
	const displayStyleOptions = getDisplayStyleOptions(
		parentBlockName,
		contextKey
	);

	const { insertBlock, replaceBlock } = useDispatch( 'core/block-editor' );

	const [ displayStyleBlocksAttributes, setDisplayStyleBlocksAttributes ] =
		useState< Record< string, Record< string, unknown > > >( {} );

	if ( displayStyleOptions.length === 0 ) return null;

	return (
		<ToggleGroupControl
			value={ currentStyle }
			isBlock
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			label=""
			hideLabelFromVision
			onChange={ ( value: string | number | undefined ) => {
				if ( ! value || typeof value !== 'string' ) {
					return;
				}
				if ( ! parentBlock ) {
					return;
				}
				if (
					! displayStyleOptions.some(
						( blockType ) => blockType.name === value
					)
				) {
					return;
				}
				const currentStyleBlock = getCurrentDisplayStyleBlock(
					parentBlock,
					displayStyleOptions
				);

				if ( currentStyleBlock ) {
					const nextDisplayStyleBlocksAttributes = {
						...displayStyleBlocksAttributes,
						[ currentStyleBlock.name ]:
							currentStyleBlock.attributes,
					};

					setDisplayStyleBlocksAttributes(
						nextDisplayStyleBlocksAttributes
					);
					void replaceBlock(
						currentStyleBlock.clientId,
						createBlock(
							value,
							nextDisplayStyleBlocksAttributes[ value ] || {}
						)
					);
				} else {
					const insertionPoint = getDisplayStyleInsertionPoint(
						parentBlock,
						getFallbackDisplayStyleInsertionPoint
					);

					void insertBlock(
						createBlock( value ),
						insertionPoint.index,
						insertionPoint.rootClientId,
						false
					);
				}
				onChange( value );
			} }
			style={ { width: '100%' } }
		>
			{ displayStyleOptions.map( ( blockType ) => (
				<ToggleGroupControlOption
					key={ blockType.name }
					label={ blockType.title }
					value={ blockType.name }
				/>
			) ) }
		</ToggleGroupControl>
	);
};

export function resetDisplayStyleBlock(
	clientId: string,
	defaultStyle: string,
	getFallbackDisplayStyleInsertionPoint?: GetFallbackDisplayStyleInsertionPoint,
	contextKey = SELECTABLE_ITEMS_CONTEXT
) {
	const parentBlock = select( 'core/block-editor' ).getBlock( clientId );
	if ( ! parentBlock ) return;

	const displayStyleOptions = getDisplayStyleOptions(
		parentBlock.name,
		contextKey
	);

	if (
		! displayStyleOptions.some(
			( blockType ) => blockType.name === defaultStyle
		)
	) {
		return;
	}

	const currentStyleBlock = getCurrentDisplayStyleBlock(
		parentBlock,
		displayStyleOptions
	);

	const { insertBlock, replaceBlock } = dispatch( 'core/block-editor' );
	if ( currentStyleBlock ) {
		replaceBlock( currentStyleBlock.clientId, createBlock( defaultStyle ) );
	} else {
		const insertionPoint = getDisplayStyleInsertionPoint(
			parentBlock,
			getFallbackDisplayStyleInsertionPoint
		);

		insertBlock(
			createBlock( defaultStyle ),
			insertionPoint.index,
			insertionPoint.rootClientId,
			false
		);
	}
}
