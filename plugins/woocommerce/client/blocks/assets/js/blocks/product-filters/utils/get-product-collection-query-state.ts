type ProductCollectionQueryState = Record< string, string >;

const isRecord = ( value: unknown ): value is Record< string, unknown > =>
	typeof value === 'object' && value !== null && ! Array.isArray( value );

const isValidTaxonomySlug = ( value: unknown ): value is string =>
	typeof value === 'string' && /^[a-z0-9_-]+$/.test( value );

const productCollectionTaxQueryExclusions = new Set( [
	'product_shipping_class',
	'product_visibility',
] );

const getValidIds = ( value: unknown ): number[] => {
	if ( ! Array.isArray( value ) ) {
		return [];
	}

	return [
		...new Set(
			value.filter(
				( termId ): termId is number =>
					typeof termId === 'number' &&
					Number.isInteger( termId ) &&
					termId > 0
			)
		),
	].sort( ( a, b ) => a - b );
};

const getTaxQueryTerms = ( value: unknown ): Map< string, number[] > => {
	const terms = new Map< string, number[] >();

	if ( ! isRecord( value ) ) {
		return terms;
	}

	Object.entries( value ).forEach( ( [ taxonomy, termIds ] ) => {
		const validTermIds = getValidIds( termIds );

		if (
			isValidTaxonomySlug( taxonomy ) &&
			! productCollectionTaxQueryExclusions.has( taxonomy ) &&
			validTermIds.length
		) {
			terms.set( taxonomy, validTermIds );
		}
	} );

	return terms;
};

const getAttributeTerms = ( value: unknown ): Map< string, number[] > => {
	const groupedTerms = new Map< string, Set< number > >();

	if ( ! Array.isArray( value ) ) {
		return new Map();
	}

	value.forEach( ( attribute ) => {
		if ( ! isRecord( attribute ) ) {
			return;
		}

		const { taxonomy, termId } = attribute;
		if (
			! isValidTaxonomySlug( taxonomy ) ||
			typeof termId !== 'number' ||
			! Number.isInteger( termId ) ||
			termId <= 0
		) {
			return;
		}

		if ( ! groupedTerms.has( taxonomy ) ) {
			groupedTerms.set( taxonomy, new Set() );
		}
		groupedTerms.get( taxonomy )?.add( termId );
	} );

	return new Map(
		[ ...groupedTerms.entries() ].map( ( [ taxonomy, termIds ] ) => [
			taxonomy,
			[ ...termIds ].sort( ( a, b ) => a - b ),
		] )
	);
};

const getTaxQueryParam = ( taxonomy: string ): string => {
	switch ( taxonomy ) {
		case 'product_cat':
			return 'category';
		case 'product_tag':
			return 'tag';
		case 'product_brand':
			return 'brand';
		default:
			return `_unstable_tax_${ taxonomy }`;
	}
};

/**
 * Adapts representable local Product Collection constraints to Store API query state.
 *
 * @param query Inherited query context.
 * @return Query state, or null for an unrecognized query context.
 */
export const getProductCollectionQueryState = (
	query: unknown
): ProductCollectionQueryState | null => {
	if (
		! isRecord( query ) ||
		query.isProductCollectionBlock !== true ||
		query.inherit !== false
	) {
		return null;
	}

	const taxQueryTerms = getTaxQueryTerms( query.taxQuery );
	const attributeTerms = getAttributeTerms( query.woocommerceAttributes );
	const taxonomies = [
		...new Set( [ ...taxQueryTerms.keys(), ...attributeTerms.keys() ] ),
	].sort();
	const queryState: ProductCollectionQueryState = {};

	taxonomies.forEach( ( taxonomy ) => {
		const hasTaxQueryTerms = taxQueryTerms.has( taxonomy );
		const hasAttributeTerms = attributeTerms.has( taxonomy );

		if (
			hasTaxQueryTerms &&
			hasAttributeTerms &&
			taxQueryTerms.get( taxonomy )?.join( ',' ) !==
				attributeTerms.get( taxonomy )?.join( ',' )
		) {
			// Independent IN groups cannot generally be flattened into one clause.
			return;
		}

		const termIds = hasTaxQueryTerms
			? taxQueryTerms.get( taxonomy )
			: attributeTerms.get( taxonomy );
		const param = getTaxQueryParam( taxonomy );
		if ( param.endsWith( '_operator' ) ) {
			// Store API reserves this suffix for operator enum parameters.
			return;
		}

		queryState[ param ] = termIds?.join( ',' ) ?? '';
		if ( hasTaxQueryTerms ) {
			queryState[ `taxonomy_include_children[${ taxonomy }]` ] = 'false';
		}
	} );

	return queryState;
};
