/**
 * External dependencies
 */
import { Block, createBlock, getBlockTypes } from '@wordpress/blocks';
import { useState } from '@wordpress/element';
import { dispatch, useSelect } from '@wordpress/data';
import {
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// @ts-expect-error - no types.
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { getInnerBlockByName } from '../../utils/get-inner-block-by-name';

export const DisplayStyleSwitcher = ( {
	clientId,
	currentStyle,
	onChange,
	parentBlockName,
}: {
	clientId: string;
	currentStyle: string;
	onChange: ( value: string ) => void;
	parentBlockName: string;
} ) => {
	const displayStyleOptions = getBlockTypes().filter( ( blockType ) =>
		blockType.ancestor?.includes( parentBlockName )
	);

	const { insertBlock, replaceBlock } = dispatch( 'core/block-editor' );
	const filterBlock = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getBlock( clientId );
		},
		[ clientId ]
	);

	const [ displayStyleBlocksAttributes, setDisplayStyleBlocksAttributes ] =
		useState< Record< string, unknown > >( {} );
	return (
		<ToggleGroupControl
			value={ currentStyle }
			isBlock
			__nextHasNoMarginBottom
			__next40pxDefaultSize
			onChange={ ( value: string ) => {
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
