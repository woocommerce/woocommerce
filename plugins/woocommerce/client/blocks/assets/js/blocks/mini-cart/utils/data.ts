/**
 * External dependencies
 */
import type { ColorPaletteOption } from '@woocommerce/editor-components/color-panel/types';

/**
 * Internal dependencies
 */
import { Attributes } from '../edit';

interface MaybeInCompatibleAttributes
	extends Omit<
		Attributes,
		'priceColor' | 'iconColor' | 'productCountColor'
	> {
	priceColorValue?: string;
	iconColorValue?: string;
	productCountColorValue?: string;
	priceColor: Partial< ColorPaletteOption > | string;
	iconColor: Partial< ColorPaletteOption > | string;
	productCountColor: Partial< ColorPaletteOption > | string;
}

export function migrateAttributesToColorPanel(
	attributes: MaybeInCompatibleAttributes
): Attributes {
	const attrs = { ...attributes };

	if ( attrs.priceColorValue && ! attrs.priceColor ) {
		attrs.priceColor = {
			color: attributes.priceColorValue as string,
		};
		delete attrs.priceColorValue;
	}

	if ( attrs.iconColorValue && ! attrs.iconColor ) {
		attrs.iconColor = {
			color: attributes.iconColorValue as string,
		};
		delete attrs.iconColorValue;
	}

	if ( attrs.productCountColorValue && ! attrs.productCountColor ) {
		attrs.productCountColor = {
			color: attributes.productCountColorValue as string,
		};
		delete attrs.productCountColorValue;
	}

	return < Attributes >attrs;
}
