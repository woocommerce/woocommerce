/**
 * External dependencies
 */
import {
	store as interactivityStore,
	navigate,
} from '@woocommerce/interactivity';
import { DropdownContext } from '@woocommerce/interactivity-components/dropdown';
import { HTMLElementEvent } from '@woocommerce/types';

type AttributeFilterContext = {
	attributeSlug: string;
	queryType: 'or' | 'and';
	selectType: 'single' | 'multiple';
};

interface AttributeFilterDropdownContext
	extends AttributeFilterContext,
		DropdownContext {}

type ActionProps = {
	event: HTMLElementEvent< HTMLInputElement >;
	context: AttributeFilterContext;
};

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

interactivityStore( {
	state: {
		filters: {},
	},
	actions: {
		filters: {
			navigateWithAttributeFilter: ( {
				context,
			}: {
				context: AttributeFilterDropdownContext;
			} ) => {
				if ( context.woocommerceDropdown.selectedItem.value ) {
					navigate(
						getUrl(
							[ context.woocommerceDropdown.selectedItem.value ],
							context.attributeSlug,
							context.queryType
						)
					);
				}
			},
			updateProductsWithAttributeFilter: ( {
				event,
				context,
			}: ActionProps ) => {
				if ( ! event.target.value ) return;

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
				navigate(
					getUrl(
						selectedTerms,
						context.attributeSlug,
						context.queryType
					)
				);
			},
		},
	},
} );
