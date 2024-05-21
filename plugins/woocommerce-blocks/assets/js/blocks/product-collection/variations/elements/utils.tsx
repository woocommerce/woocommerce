/**
 * External dependencies
 */
import { registerBlockVariation } from '@wordpress/blocks';

interface VariationDetails {
	blockDescription: string;
	blockIcon: JSX.Element;
	blockTitle: string;
	variationName: string;
	attributes?: object;
}

export function registerElementVariation(
	coreName: string,
	{
		blockDescription,
		blockIcon,
		blockTitle,
		variationName,
		attributes = {},
	}: VariationDetails
) {
	registerBlockVariation( coreName, {
		description: blockDescription,
		name: variationName,
		title: blockTitle,
		isActive: ( blockAttributes ) =>
			blockAttributes.__woocommerceNamespace === variationName,
		icon: {
			src: blockIcon,
		},
		attributes: {
			__woocommerceNamespace: variationName,
			...attributes,
		},
		scope: [ 'block', 'inserter' ],
	} );
}
