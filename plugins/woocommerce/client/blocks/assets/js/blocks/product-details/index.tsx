/**
 * External dependencies
 */
import { registerProductBlockType } from '@woocommerce/atomic-utils';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from './save';
import edit from './edit';
import icon from './icon';
import './style.scss';

const blockConfig = {
	...metadata,
	icon,
	edit,
	save,
	deprecated: [
		{
			attributes: {
				hideTabTitle: {
					type: 'boolean',
					default: false,
				},
			},
			save() {
				return null;
			},
		},
	],
};
// @ts-expect-error blockConfig is not typed.
registerProductBlockType( blockConfig );
