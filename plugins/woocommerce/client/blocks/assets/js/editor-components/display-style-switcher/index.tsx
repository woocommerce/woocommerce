/**
 * External dependencies
 */
import {
	createBlock,
	getBlockSupport,
	getBlockTypes,
	type BlockInstance,
} from '@wordpress/blocks';
import { useState } from '@wordpress/element';
import { dispatch, select, useDispatch } from '@wordpress/data';
import { getInnerBlockByName } from '@woocommerce/utils';
import {
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

const SELECTABLE_ITEMS_CONTEXT = 'woocommerce/selectableItems';

type DisplayStyleInsertionPoint = {
	rootClientId: string;
	index: number;
};

type DisplayStyleBlockType = ReturnType< typeof getBlockTypes >[ number ] & {
	ancestor?: readonly string[] | string;
	usesContext?: readonly string[] | string;
};

type DisplayStyleSwitcherProps = {
	clientId: string;
	currentStyle: string;
	onChange: ( value: string ) => void;
	contextKey?: string;
	getFallbackInsertionPoint?: (
		parentBlock: BlockInstance
	) => DisplayStyleInsertionPoint;
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

function isDisplayStyleCandidate(
	blockType: DisplayStyleBlockType,
	parentBlockName: string | undefined,
	contextKey: string
): boolean {
	if ( ! parentBlockName ) {
		return false;
	}

	if (
		getBlockSupport(
			blockType.name,
			'woocommerce.innerBlockDisplayStyle',
			false
		) !== true
	) {
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
	getFallbackInsertionPoint?: (
		parentBlock: BlockInstance
	) => DisplayStyleInsertionPoint
): DisplayStyleInsertionPoint {
	return (
		getFallbackInsertionPoint?.( parentBlock ) ?? {
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
	getFallbackInsertionPoint,
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
				if ( ! value || typeof value !== 'string' ) return;
				if ( ! parentBlock ) return;
				const currentStyleBlock = getInnerBlockByName(
					parentBlock,
					currentStyle
				);

				if ( currentStyleBlock ) {
					const nextDisplayStyleBlocksAttributes = {
						...displayStyleBlocksAttributes,
						[ currentStyle ]: currentStyleBlock.attributes,
					};

					setDisplayStyleBlocksAttributes(
						nextDisplayStyleBlocksAttributes
					);
					replaceBlock(
						currentStyleBlock.clientId,
						createBlock(
							value,
							nextDisplayStyleBlocksAttributes[ value ] || {}
						)
					);
				} else {
					const insertionPoint = getDisplayStyleInsertionPoint(
						parentBlock,
						getFallbackInsertionPoint
					);

					insertBlock(
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
	getFallbackInsertionPoint?: (
		parentBlock: BlockInstance
	) => DisplayStyleInsertionPoint,
	contextKey = SELECTABLE_ITEMS_CONTEXT
) {
	const parentBlock = select( 'core/block-editor' ).getBlock( clientId );
	if ( ! parentBlock ) return;

	const displayStyleOptions = getDisplayStyleOptions(
		parentBlock.name,
		contextKey
	);
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
			getFallbackInsertionPoint
		);

		insertBlock(
			createBlock( defaultStyle ),
			insertionPoint.index,
			insertionPoint.rootClientId,
			false
		);
	}
}
