/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import formStepAttributes from '../../form-step/attributes';
import { DEFAULT_TITLE } from './constants';

const attributes: BlockAttributes = {
	...formStepAttributes( {
		defaultTitle: DEFAULT_TITLE,
		defaultDescription: '',
	} ),
	className: {
		type: 'string',
		default: '',
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
export default attributes;
