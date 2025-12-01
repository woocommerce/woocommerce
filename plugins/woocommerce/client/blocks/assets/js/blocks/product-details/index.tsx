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
import { Attributes } from './types';

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
			migrate( attributes: Attributes ) {
				return {
					...attributes,
					// In the previous version of this block, we didn't define the align attribute.
					// Because of that, align is missing from attributes argument here.
					// We need to add it manually as wide is the previous default value.
					align: 'wide',
				};
			},
		},
	],
};
// @ts-expect-error blockConfig is not typed.
registerProductBlockType( blockConfig, {
	isAvailableOnPostEditor: true,
} );
