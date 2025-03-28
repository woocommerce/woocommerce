/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import metadata from './block.json';
import { supports } from './supports';

import { BLOCK_ICON as icon } from './constants';

const blockConfig: BlockConfiguration = {
	...metadata,
	...sharedConfig,
	icon: { src: icon },
	supports,
	edit,
	save: () => null,
};

registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
