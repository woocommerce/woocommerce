/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { store as coreStore } from '@wordpress/core-data';
import { registerBlockBindingsSource } from '@wordpress/blocks';
import type { select as registrySelect } from '@wordpress/data';

interface GetValuesArgs {
	context: Record< string, unknown >;
	bindings?: Record<
		string,
		{
			args?: {
				size?: string;
				attachmentId?: number;
				noPlaceholder?: boolean;
			};
		}
	>;
	select: typeof registrySelect;
}

export const registerTermImageBinding = () => {
	registerBlockBindingsSource( {
		name: 'woocommerce/term-image',
		label: __( 'Product category image', 'woocommerce' ),
		usesContext: [
			'termId',
			'termTaxonomy',
			'taxonomy',
			'woocommerce/termImageId',
			'woocommerce/termImageUrl',
		],
		getValues( { context, bindings, select }: GetValuesArgs ) {
			const args = bindings?.url?.args || bindings?.id?.args;
			const id =
				args?.attachmentId || context[ 'woocommerce/termImageId' ];
			const media =
				args?.size && id && select
					? select( coreStore ).getMedia( Number( id ) )
					: undefined;
			const url =
				media?.media_details?.sizes?.[ args?.size || 'full' ]
					?.source_url ||
				media?.source_url ||
				context[ 'woocommerce/termImageUrl' ];
			return {
				id,
				url: ! id && args?.noPlaceholder ? '' : url,
			};
		},
	} );
};
