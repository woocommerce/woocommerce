/**
 * External dependencies
 */
import { previewProducts } from '@woocommerce/resource-previews';
import type { Block } from '@wordpress/blocks';

type ExampleBlock = Block[ 'example' ] & {
	attributes: {
		productId: 'preview' | number;
		previewProduct: typeof previewProducts[ number ];
	};
};

export const example: ExampleBlock = {
	attributes: {
		productId: 'preview',
		previewProduct: previewProducts[ 0 ],
	},
} as const;
