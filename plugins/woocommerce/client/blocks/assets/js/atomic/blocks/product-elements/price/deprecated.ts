/**
 * Internal dependencies
 */
import metadata from './block.json';
import save from '../save';

const { attributes: blockAttributes } = metadata;

// In https://github.com/woocommerce/woocommerce/pull/57980, the `isDescendentOf*` attributes were removed from editor-load updates and block attributes.
const v1 = {
	attributes: {
		...blockAttributes,
		isDescendentOfQueryLoop: { type: 'boolean', default: false },
		isDescendentOfSingleProductTemplate: {
			type: 'boolean',
			default: false,
		},
		isDescendentOfSingleProductBlock: {
			type: 'boolean',
			default: false,
		},
	},
	save,
	apiVersion: 3,
};

const deprecated = [ v1 ];

export default deprecated;
