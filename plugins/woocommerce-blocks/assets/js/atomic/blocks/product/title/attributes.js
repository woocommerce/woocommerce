/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

let blockAttributes = {
	headingLevel: {
		type: 'number',
		default: 2,
	},
	productLink: {
		type: 'boolean',
		default: true,
	},
};

if ( isFeaturePluginBuild() ) {
	blockAttributes = {
		...blockAttributes,
		align: {
			type: 'string',
		},
		color: {
			type: 'string',
		},
		customColor: {
			type: 'string',
		},
		fontSize: {
			type: 'string',
		},
		customFontSize: {
			type: 'number',
		},
	};
}
export default blockAttributes;
