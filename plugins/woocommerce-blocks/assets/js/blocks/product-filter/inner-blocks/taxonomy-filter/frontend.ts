/**
 * External dependencies
 */
import { store, getContext } from '@woocommerce/interactivity';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';
import { HTMLElementEvent } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { navigate } from '../../frontend';

type TaxonomyFilterContext = {
	taxonomyName: string;
	queryType: 'or' | 'and';
	selectType: 'single' | 'multiple';
};

interface ActiveTaxonomyFilterContext extends TaxonomyFilterContext {
	value: string;
}

function nonNullable< T >( value: T ): value is NonNullable< T > {
	return value !== null && value !== undefined;
}

function getUrl(
	selectedTerms: string[],
	slug: string,
	queryType: 'or' | 'and'
) {
	const url = new URL( window.location.href );
	const { searchParams } = url;

	if ( selectedTerms.length > 0 ) {
		searchParams.set( `filter_${ slug }`, selectedTerms.join( ',' ) );
		searchParams.set( `query_type_${ slug }`, queryType );
	} else {
		searchParams.delete( `filter_${ slug }` );
		searchParams.delete( `query_type_${ slug }` );
	}

	return url.href;
}

function getSelectedTermsFromUrl( slug: string ) {
	const url = new URL( window.location.href );
	return ( url.searchParams.get( `filter_${ slug }` ) || '' )
		.split( ',' )
		.filter( Boolean );
}

store( 'woocommerce/product-filter-taxonomy', {
	actions: {
		navigate: () => {
			const dropdownContext = getContext< DropdownContext >(
				'woocommerce/interactivity-dropdown'
			);
			const context = getContext< TaxonomyFilterContext >();
			const filters = dropdownContext.selectedItems
				.map( ( item ) => item.value )
				.filter( nonNullable );

			navigate(
				getUrl( filters, context.taxonomyName, context.queryType )
			);
		},
		updateProducts: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			if ( ! event.target.value ) return;

			const context = getContext< TaxonomyFilterContext >();

			let selectedTerms = getSelectedTermsFromUrl( context.taxonomyName );

			if (
				event.target.checked &&
				! selectedTerms.includes( event.target.value )
			) {
				if ( context.selectType === 'multiple' )
					selectedTerms.push( event.target.value );
				if ( context.selectType === 'single' )
					selectedTerms = [ event.target.value ];
			} else {
				selectedTerms = selectedTerms.filter(
					( value ) => value !== event.target.value
				);
			}

			navigate(
				getUrl( selectedTerms, context.taxonomyName, context.queryType )
			);
		},
		removeFilter: () => {
			const { taxonomyName, queryType, value } =
				getContext< ActiveTaxonomyFilterContext >();

			let selectedTerms = getSelectedTermsFromUrl( taxonomyName );

			selectedTerms = selectedTerms.filter( ( item ) => item !== value );

			navigate( getUrl( selectedTerms, taxonomyName, queryType ) );
		},
	},
} );
