/**
 * External dependencies
 */
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import sharedConfig from '../shared/config';
import edit from './edit';
import { BLOCK_ICON as icon } from './constants';
import { supports } from './support';

export const ProductRatingBlockSettings: BlockConfiguration = {
	...sharedConfig,
	icon: { src: icon },
	supports,
	//@ts-expect-error `edit` can have a different type than `BlockConfiguration`
	edit,
};
