/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	FormTokenField,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import {
	AttributeMetadata,
	AttributeWithTerms,
	ProductQueryBlock,
} from '../types';
import useProductAttributes from '../useProductAttributes';
import { setQueryAttribute } from '../utils';

function getAttributeMetadataFromToken(
	token: string,
	productsAttributes: AttributeWithTerms[]
) {
	const [ attributeLabel, termName ] = token.split( ': ' );
	const taxonomy = productsAttributes.find(
		( attribute ) => attribute.attribute_label === attributeLabel
	);

	if ( ! taxonomy )
		throw new Error( 'Product Query Filter: Invalid attribute label' );

	const term = taxonomy.terms.find(
		( currentTerm ) => currentTerm.name === termName
	);

	if ( ! term ) throw new Error( 'Product Query Filter: Invalid term name' );

	return {
		taxonomy: `pa_${ taxonomy.attribute_name }`,
		termId: term.id,
	};
}

function getAttributeFromMetadata(
	metadata: AttributeMetadata,
	productsAttributes: AttributeWithTerms[]
) {
	const taxonomy = productsAttributes.find(
		( attribute ) =>
			attribute.attribute_name === metadata.taxonomy.slice( 3 )
	);

	return {
		taxonomy,
		term: taxonomy?.terms.find( ( term ) => term.id === metadata.termId ),
	};
}

function getInputValueFromQueryParam(
	queryParam: AttributeMetadata[] | undefined,
	productAttributes: AttributeWithTerms[]
): FormTokenField.Value[] {
	return (
		queryParam?.map( ( metadata ) => {
			const { taxonomy, term } = getAttributeFromMetadata(
				metadata,
				productAttributes
			);

			return ! taxonomy || ! term
				? {
						title: __(
							'Saved taxonomy was perhaps deleted or the slug was changed.',
							'woo-gutenberg-products-block'
						),
						value: __(
							`Error with saved taxonomy`,
							'woo-gutenberg-products-block'
						),
						status: 'error',
				  }
				: `${ taxonomy.attribute_label }: ${ term.name }`;
		} ) || []
	);
}

export const AttributesFilter = ( props: ProductQueryBlock ) => {
	const { query } = props.attributes;
	const { isLoadingAttributes, productsAttributes } =
		useProductAttributes( true );

	const attributesSuggestions = productsAttributes.reduce( ( acc, curr ) => {
		const namespacedTerms = curr.terms.map(
			( term ) => `${ curr.attribute_label }: ${ term.name }`
		);

		return [ ...acc, ...namespacedTerms ];
	}, [] as string[] );

	return (
		<ToolsPanelItem
			label={ __( 'Product Attributes', 'woo-gutenberg-products-block' ) }
			hasValue={ () => query.__woocommerceAttributes?.length }
		>
			<FormTokenField
				disabled={ isLoadingAttributes }
				label={ __(
					'Product Attributes',
					'woo-gutenberg-products-block'
				) }
				onChange={ ( attributes ) => {
					let __woocommerceAttributes;

					try {
						__woocommerceAttributes = attributes.map(
							( attribute ) => {
								attribute =
									typeof attribute === 'string'
										? attribute
										: attribute.value;

								return getAttributeMetadataFromToken(
									attribute,
									productsAttributes
								);
							}
						);

						setQueryAttribute( props, {
							__woocommerceAttributes,
						} );
					} catch ( ok ) {
						// Not required to do anything here
						// Input validation is handled by the `validateInput`
						// below, and we don't need to save anything.
					}
				} }
				suggestions={ attributesSuggestions }
				validateInput={ ( value: string ) =>
					attributesSuggestions.includes( value )
				}
				value={
					isLoadingAttributes
						? [ __( 'Loading…', 'woo-gutenberg-products-block' ) ]
						: getInputValueFromQueryParam(
								query.__woocommerceAttributes,
								productsAttributes
						  )
				}
				__experimentalExpandOnFocus={ true }
			/>
		</ToolsPanelItem>
	);
};
