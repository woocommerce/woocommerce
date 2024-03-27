/**
 * External dependencies
 */
import {
	BlockInstance,
	createBlock,
	registerBlockType,
} from '@wordpress/blocks';
import { Icon, more } from '@wordpress/icons';
import { isExperimentalBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';
import { BLOCK_NAME_MAP, BLOCK_TITLE_MAP } from './constants';
import { BlockAttributes } from './types';

if ( isExperimentalBuild() ) {
	for ( const filterType of Object.keys( BLOCK_NAME_MAP ) as Array<
		keyof typeof BLOCK_NAME_MAP
	> ) {
		const blockMeta = {
			...metadata,
			name: `${ BLOCK_NAME_MAP[ filterType ] }-wrapper`,
			title: BLOCK_TITLE_MAP[ filterType ],
			attributes: {
				...metadata.attributes,
				filterType: {
					type: 'string',
					default: filterType,
				},
				heading: {
					type: 'string',
					default: BLOCK_TITLE_MAP[ filterType ],
				},
			},
		};

		registerBlockType( blockMeta, {
			icon: {
				src: (
					<Icon
						icon={ more }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
			edit,
			save,
			transforms: {
				from: [
					{
						type: 'block',
						blocks: [ 'woocommerce/filter-wrapper' ],
						transform: (
							attributes: BlockAttributes,
							innerBlocks: BlockInstance[]
						) => {
							const newInnerBlocks: BlockInstance[] = [];
							// Loop through inner blocks to preserve the block order.
							innerBlocks.forEach( ( block ) => {
								if (
									block.name ===
									`woocommerce/${ attributes.filterType }`
								) {
									newInnerBlocks.push(
										createBlock(
											BLOCK_NAME_MAP[
												attributes.filterType
											],
											block.attributes
										)
									);
								}

								if ( block.name === 'core/heading' ) {
									newInnerBlocks.push( block );
								}
							} );

							return createBlock(
								`${ BLOCK_NAME_MAP[ filterType ] }-wrapper`,
								attributes,
								newInnerBlocks
							);
						},
					},
				],
			},
		} );
	}
}
