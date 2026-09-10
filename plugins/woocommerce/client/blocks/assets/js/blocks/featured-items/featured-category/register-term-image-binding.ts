/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

type BindingContext = Record< string, unknown >;
type RegisterBlockBindingsSource = ( settings: {
	name: string;
	label: string;
	usesContext: string[];
	getValues: ( args: {
		context: BindingContext;
	} ) => Record< string, unknown >;
} ) => void;

const wpGlobal = window as typeof window & {
	wp?: {
		blocks?: {
			registerBlockBindingsSource?: RegisterBlockBindingsSource;
		};
	};
};
export const registerTermImageBinding = () => {
	const registerBlockBindingsSource =
		wpGlobal.wp?.blocks?.registerBlockBindingsSource;

	if ( ! registerBlockBindingsSource ) {
		return;
	}

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
		getValues( { context } ) {
			return {
				id: context[ 'woocommerce/termImageId' ],
				url: context[ 'woocommerce/termImageUrl' ],
			};
		},
	} );
};
