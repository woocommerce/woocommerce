/**
 * External dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { dispatch, useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { Block, getBlockTypes, createBlock } from '@wordpress/blocks';
import {
	PanelBody,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { EditProps, BlockAttributes } from '../types';
import { getInnerBlockByName } from '../../../utils';

let displayStyleOptions: Block[] = [];

export const Inspector = ( {
	clientId,
	attributes,
	setAttributes,
}: EditProps ) => {
	const { displayStyle } = attributes;
	const { insertBlock, replaceBlock } = dispatch( 'core/block-editor' );
	const filterBlock = useSelect(
		( select ) => {
			return select( 'core/block-editor' ).getBlock( clientId );
		},
		[ clientId ]
	);
	const [ displayStyleBlocksAttributes, setDisplayStyleBlocksAttributes ] =
		useState< Record< string, unknown > >( {} );

	if ( displayStyleOptions.length === 0 ) {
		displayStyleOptions = getBlockTypes().filter( ( blockType ) =>
			blockType.ancestor?.includes( 'woocommerce/product-filter-active' )
		);
	}

	return (
		<InspectorControls group="styles">
			<PanelBody title={ __( 'Display', 'woocommerce' ) }>
				<ToggleGroupControl
					value={ displayStyle }
					onChange={ ( value: BlockAttributes[ 'displayStyle' ] ) => {
						if ( ! filterBlock ) return;
						const currentStyleBlock = getInnerBlockByName(
							filterBlock,
							displayStyle
						);

						if ( currentStyleBlock ) {
							setDisplayStyleBlocksAttributes( {
								...displayStyleBlocksAttributes,
								[ displayStyle ]: currentStyleBlock.attributes,
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
						setAttributes( { displayStyle: value } );
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
			</PanelBody>
		</InspectorControls>
	);
};
