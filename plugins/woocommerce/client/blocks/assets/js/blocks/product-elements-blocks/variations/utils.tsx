/**
 * External dependencies
 */
import {
	type BlockVariationScope,
	registerBlockVariation,
} from '@wordpress/blocks';
import type { ReactElement } from '@wordpress/element';

interface VariationDetails {
	blockDescription: string;
	blockIcon: ReactElement;
	blockTitle: string;
	variationName: string;
	scope: BlockVariationScope[];
	namespaces: string[];
}

export function registerElementVariation(
	coreName: string,
	{
		blockDescription,
		blockIcon,
		blockTitle,
		variationName,
		scope,
		namespaces,
	}: VariationDetails
) {
	registerBlockVariation( coreName, {
		description: blockDescription,
		name: variationName,
		title: blockTitle,
		isActive: ( blockAttributes ) =>
			namespaces.includes( blockAttributes.__woocommerceNamespace ),
		icon: {
			src: blockIcon,
		},
		attributes: {
			__woocommerceNamespace: variationName,
		},
		scope,
	} );
}
