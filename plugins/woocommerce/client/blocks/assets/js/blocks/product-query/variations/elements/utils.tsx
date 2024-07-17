/**
 * External dependencies
 */
import {
	registerBlockVariation,
	type BlockVariationScope,
} from '@wordpress/blocks';

interface VariationDetails {
	blockDescription: string;
	blockIcon: JSX.Element;
	blockTitle: string;
	variationName: string;
	scope: BlockVariationScope[];
}

export function registerElementVariation(
	coreName: string,
	{
		blockDescription,
		blockIcon,
		blockTitle,
		variationName,
		scope,
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
		},
		scope,
	} );
}
