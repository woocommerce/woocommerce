/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import { isEmptyObject } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { supports } from './supports';
import { BLOCK_ICON as icon } from './constants';
import metadata from './block.json';
import './upgrade';

const deprecated = [
	{
		save: sharedConfig.save,
		migrate: ( attributes: BlockAttributes ) => {
			// We don't deprecate attributes, but adding new ones.
			// For backwards compatibility, some new attributes require
			// different defaults than new ones.
			return {
				...attributes,
				showDescriptionIfEmpty: true,
				summaryLength: 150,
			};
		},
		isEligible: ( attributes: BlockAttributes ) =>
			isEmptyObject( attributes ),
	},
];

const blockConfig = {
	...metadata,
	...sharedConfig,
	icon: { src: icon },
	supports,
	deprecated,
	edit,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
