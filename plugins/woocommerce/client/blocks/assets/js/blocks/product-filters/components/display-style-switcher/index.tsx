/**
 * External dependencies
 */
import { createBlock, getBlockTypes } from '@wordpress/blocks';
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

export const DisplayStyleSwitcher = ( {
	clientId,
	currentStyle,
	onChange,
}: {
	clientId: string;
	currentStyle: string;
	onChange: ( value: string ) => void;
} ) => {
	const filterBlock = select( 'core/block-editor' ).getBlock( clientId );
	const parentBlockName = filterBlock?.name;

	const displayStyleOptions = getBlockTypes().filter( ( blockType ) => {
		if ( parentBlockName ) {
			return blockType.ancestor?.includes( parentBlockName );
		}
		return [];
	} );

	const { insertBlock, replaceBlock } = useDispatch( 'core/block-editor' );

	const [ displayStyleBlocksAttributes, setDisplayStyleBlocksAttributes ] =
		useState< Record< string, unknown > >( {} );

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
				if ( ! filterBlock ) return;
				const currentStyleBlock = getInnerBlockByName(
					filterBlock,
					currentStyle
				);

				if ( currentStyleBlock ) {
					setDisplayStyleBlocksAttributes( {
						...displayStyleBlocksAttributes,
						[ currentStyle ]: currentStyleBlock.attributes,
					} );
					replaceBlock(
						currentStyleBlock.clientId,
						createBlock(
							value,
							displayStyleBlocksAttributes[ value ] || {}
						)
					);
				} else {
					insertBlock(
						createBlock( value ),
						filterBlock.innerBlocks.length,
						filterBlock.clientId,
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
	defaultStyle: string
) {
	const filterBlock = select( 'core/block-editor' ).getBlock( clientId );
	if ( ! filterBlock ) return;

	const parentBlockName = filterBlock.name;
	const displayStyleOptions = getBlockTypes().filter( ( blockType ) =>
		blockType.ancestor?.includes( parentBlockName )
	);

	const currentStyle = displayStyleOptions.find( ( blockType ) =>
		getInnerBlockByName( filterBlock, blockType.name )
	);

	const currentStyleBlock = currentStyle
		? getInnerBlockByName( filterBlock, currentStyle.name )
		: null;

	const { insertBlock, replaceBlock } = dispatch( 'core/block-editor' );
	if ( currentStyleBlock ) {
		replaceBlock( currentStyleBlock.clientId, createBlock( defaultStyle ) );
	} else {
		insertBlock(
			createBlock( defaultStyle ),
			filterBlock.innerBlocks.length,
			filterBlock.clientId,
			false
		);
	}
}
