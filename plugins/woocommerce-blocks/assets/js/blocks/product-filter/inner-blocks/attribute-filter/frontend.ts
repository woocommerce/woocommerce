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

type AttributeFilterContext = {
	attributeSlug: string;
	queryType: 'or' | 'and';
	selectType: 'single' | 'multiple';
};

interface ActiveAttributeFilterContext extends AttributeFilterContext {
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
const { state } = store( 'woocommerce/product-filter' );

store( 'woocommerce/product-filter-attribute', {
	actions: {
		navigate: () => {
			const dropdownContext = getContext< DropdownContext >(
				'woocommerce/interactivity-dropdown'
			);
			const context = getContext< AttributeFilterContext >();
			const filters = dropdownContext.selectedItems
				.map( ( item ) => item.value )
				.filter( nonNullable );

			navigate(
				getUrl( filters, context.attributeSlug, context.queryType )
			);
		},
		updateProducts: ( event: HTMLElementEvent< HTMLInputElement > ) => {
			if ( ! event.target.value ) return;

			const context = getContext< AttributeFilterContext >();

			let selectedTerms = getSelectedTermsFromUrl(
				context.attributeSlug
			);

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

			if ( selectedTerms.length > 0 ) {
				state.hasSelectedFilter = true;
			} else {
				state.hasSelectedFilter = false;
			}

			navigate(
				getUrl(
					selectedTerms,
					context.attributeSlug,
					context.queryType
				)
			);
		},
		removeFilter: () => {
			const { attributeSlug, queryType, value } =
				getContext< ActiveAttributeFilterContext >();

			let selectedTerms = getSelectedTermsFromUrl( attributeSlug );

			selectedTerms = selectedTerms.filter( ( item ) => item !== value );

			navigate( getUrl( selectedTerms, attributeSlug, queryType ) );
		},
	},
} );
