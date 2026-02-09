/**
 * WordPress dependencies
 */

/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from '../save';

const { attributes: blockAttributes } = metadata;

// In https://github.com/woocommerce/woocommerce/pull/57980, the `isDescendentOfQueryLoop` and `isDescendentOfSingleProductTemplate` attributes were removed.
const v1 = {
	attributes: {
		...blockAttributes,
		isDescendentOfQueryLoop: { type: 'boolean', default: false },
		isDescendentOfSingleProductTemplate: {
			type: 'boolean',
			default: false,
		},
	},
	save,
	apiVersion: 3,
};

const deprecated = [ v1 ];

export default deprecated;
