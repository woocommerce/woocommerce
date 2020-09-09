/**
 * External dependencies
 */
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

let blockAttributes = {};

if ( isFeaturePluginBuild() ) {
	blockAttributes = {
		...blockAttributes,
		align: {
			type: 'string',
		},
		fontSize: {
			type: 'string',
		},
		customFontSize: {
			type: 'number',
		},
		saleFontSize: {
			type: 'string',
		},
		customSaleFontSize: {
			type: 'number',
		},
		color: {
			type: 'string',
		},
		saleColor: {
			type: 'string',
		},
		customColor: {
			type: 'string',
		},
		customSaleColor: {
			type: 'string',
		},
	};
}
export default blockAttributes;
