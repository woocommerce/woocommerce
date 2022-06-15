/**
 * External dependencies
 */
import { addQueryArgs, removeQueryArgs } from '@wordpress/url';
import { QueryArgs } from '@wordpress/url/build-types/get-query-args';
import {
	getUrlParameter,
	PREFIX_QUERY_ARG_FILTER_TYPE,
	PREFIX_QUERY_ARG_QUERY_TYPE,
} from '@woocommerce/utils';
import { AttributeObject } from '@woocommerce/types';

interface Param {
	attribute: string;
	operator: string;
	slug: Array< string >;
}

export const parseTaxonomyToGenerateURL = ( taxonomy: string ) =>
	taxonomy.replace( 'pa_', '' );

export const formatParams = ( url: string, params: Array< Param > = [] ) => {
	const paramObject: Record< string, string > = {};

	params.forEach( ( param ) => {
		const { attribute, slug, operator } = param;

		// Custom filters are prefix with `pa_` so we need to remove this.
		const name = parseTaxonomyToGenerateURL( attribute );
		const values = slug.join( ',' );
		const queryType = `${ PREFIX_QUERY_ARG_QUERY_TYPE }${ name }`;
		const type = operator === 'in' ? 'or' : 'and';

		// The URL parameter requires the prefix filter_ with the attribute name.
		paramObject[ `${ PREFIX_QUERY_ARG_FILTER_TYPE }${ name }` ] = values;
		paramObject[ queryType ] = type;
	} );

	// Clean the URL before we add our new query parameters to it.
	const cleanUrl = removeQueryArgs( url, ...Object.keys( paramObject ) );

	return addQueryArgs( cleanUrl, paramObject );
};

export const areAllFiltersRemoved = ( {
	currentCheckedFilters,
	hasSetPhpFilterDefaults,
}: {
	currentCheckedFilters: Array< string >;
	hasSetPhpFilterDefaults: boolean;
} ) => hasSetPhpFilterDefaults && currentCheckedFilters.length === 0;

export const getActiveFilters = (
	isFilteringForPhpTemplateEnabled: boolean,
	attributeObject: AttributeObject | undefined
) => {
	if ( isFilteringForPhpTemplateEnabled && attributeObject ) {
		const defaultAttributeParam = getUrlParameter(
			`filter_${ attributeObject.name }`
		);
		const defaultCheckedValue =
			typeof defaultAttributeParam === 'string'
				? defaultAttributeParam.split( ',' )
				: [];

		return defaultCheckedValue;
	}

	return [];
};

export const isQueryArgsEqual = (
	currentQueryArgs: QueryArgs,
	newQueryArgs: QueryArgs
) => {
	// The user can add same two filter blocks for the same attribute.
	// We removed the query type from the check to avoid refresh loop.
	const filteredNewQueryArgs = Object.entries( newQueryArgs ).reduce(
		( acc, [ key, value ] ) => {
			return key.includes( 'query_type' )
				? acc
				: {
						...acc,
						[ key ]: value,
				  };
		},
		{}
	);

	return Object.entries( filteredNewQueryArgs ).reduce(
		( isEqual, [ key, value ] ) =>
			currentQueryArgs[ key ] === value ? isEqual : false,
		true
	);
};
