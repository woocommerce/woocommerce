/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

let blockAttributes: Record< string, Record< string, unknown > > = {
	headingLevel: {
		type: 'number',
		default: 2,
	},
	showProductLink: {
		type: 'boolean',
		default: true,
	},
	productId: {
		type: 'number',
		default: 0,
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
