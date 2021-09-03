/**
 * External dependencies
 */
import { registerBlockComponent } from '@woocommerce/blocks-registry';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import type { CheckoutBlockOptions } from './types';
import {
	assertBlockName,
	assertOption,
	assertBlockComponent,
	assertValidArea,
} from './utils';
import { registeredBlocks } from './registered-blocks';

/**
 * Main API for registering a new checkout block within areas.
 */
export const registerCheckoutBlock = (
	blockName: string,
	options: CheckoutBlockOptions
): void => {
	assertBlockName( blockName );
	assertOption( options, 'areas', 'array' );
	assertBlockComponent( options, 'component' );

	/**
	 * This ensures the frontend component for the checkout block is available.
	 */
	registerBlockComponent( {
		blockName,
		component: options.component,
	} );

	/**
	 * If provided with a configuration object, this registers the block with WordPress.
	 */
	if ( options?.configuration ) {
		assertOption( options, 'configuration', 'object' );

		const blockConfiguration = {
			category: 'woocommerce',
			parent: [],
			...options.configuration,
		};

		if ( options.force ) {
			blockConfiguration.attributes = {
				...( options.configuration?.attributes || {} ),
				lock: {
					...( options.configuration?.attributes?.lock || {
						type: 'object',
						default: {
							remove: true,
						},
					} ),
				},
			};
		}

		registerExperimentalBlockType( blockName, blockConfiguration );
	}

	/**
	 * This enables the inner block within specific areas of the checkout. An area maps to the parent block name.
	 */
	options.areas.forEach( ( area ) => {
		assertValidArea( area );
		registeredBlocks[ area ] = [
			...registeredBlocks[ area ],
			{
				block: blockName,
				component: options.component,
				force: options?.force || false,
			},
		];
	} );
};
