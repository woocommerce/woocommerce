/**
 * External dependencies
 */
import { createElement, useEffect, useState } from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import {
	Button,
	FormTokenField as CoreFormTokenField,
} from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { cleanForSlug } from '@wordpress/url';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	type ProductAttributeTerm,
} from '@woocommerce/data';
import type { MouseEventHandler } from 'react';

/**
 * Internal dependencies
 */
import AttributesComboboxControl from '../attribute-combobox-field';
import type { AttributeTableRowProps } from './types';

interface FormTokenFieldProps extends CoreFormTokenField.Props {
	__experimentalExpandOnFocus: boolean;
	__experimentalAutoSelectFirstMatch: boolean;
	placeholder: string;
	label?: string;
}
const FormTokenField =
	CoreFormTokenField as React.ComponentType< FormTokenFieldProps >;

/*
 * Type copied form core FormTokenField component.
 * Todo: move to a shared location.
 */
export interface TokenItem {
	value: string;
	status?: 'error' | 'success' | 'validating';
	title?: string;
	isBorderless?: boolean;
	onMouseEnter?: MouseEventHandler< HTMLSpanElement >;
	onMouseLeave?: MouseEventHandler< HTMLSpanElement >;
}

/**
 * Convert a string or a TokenItem to a TokenItem.
 *
 * @param {string | TokenItem} v - The value to convert.
 * @return {TokenItem} The TokenItem.
 */
const stringToTokenItem = ( v: string | TokenItem ): TokenItem => ( {
	value: typeof v === 'string' ? v : v.value,
} );

/**
 * Convert a TokenItem to a string.
 *
 * @param {string | TokenItem} item - The item to convert.
 * @return {string} The string.
 */
const tokenItemToString = ( item: string | TokenItem ): string =>
	typeof item === 'string' ? item : item.value;

export const AttributeTableRow: React.FC< AttributeTableRowProps > = ( {
	index,
	attribute,
	attributePlaceholder,
	disabledAttributeMessage,
	isLoadingAttributes,
	attributes,
	onNewAttributeAdd,
	onAttributeSelect,

	termPlaceholder,
	onTermsSelect,

	termsAutoSelection,

	clearButtonDisabled,
	removeLabel,
	onRemove,
} ) => {
	const attributeId = attribute ? attribute.id : undefined;

	const { createProductAttributeTerm } = useDispatch(
		EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
	);

	/*
	 * Get the terms for the current attribute,
	 * used in the token field suggestions and values.
	 */
	const terms = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getProductAttributeTerms } = select(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			);

			return attributeId
				? ( getProductAttributeTerms( {
						search: '',
						attribute_id: attributeId,
				  } ) as ProductAttributeTerm[] )
				: [];
		},
		[ attributeId ]
	);

	/*
	 * Local terms to handle not global attributes.
	 * Set initially with the attribute options.
	 */
	const [ localTerms, setLocalTerms ] = useState< TokenItem[] >(
		attribute?.options?.map( stringToTokenItem ) || []
	);

	/**
	 * Use the temporary terms to store and show
	 * the new terms on the fly,
	 * before they are created by hitting the API.
	 */
	const [ temporaryTerms, setTemporaryTerms ] = useState< TokenItem[] >( [] );

	// By convention, it's a global attribute if the attribute ID is 0.
	const isGlobalAttribute = attribute?.id === 0;

	/*
	 * When isGlobalAttribute is true, allTerms is the localTerms,
	 * otherwise, it is the attribute terms (mapped to their names)
	 */
	const allTerms =
		( isGlobalAttribute
			? localTerms.map( tokenItemToString )
			: terms?.map( ( term: ProductAttributeTerm ) => term.name ) ) || [];

	/*
	 * For `suggestions` (the values of the FormTokenField component),
	 * combine the temporary terms with the attribute options or terms,
	 * removing duplicates.
	 */
	const tokenFieldSuggestions = [
		...( allTerms || [] ),
		...temporaryTerms.map( tokenItemToString ),
	].filter( ( value, i, self ) => self.indexOf( value ) === i );

	/*
	 * Build attribute terms object from the attribute,
	 * used to populate the token field.
	 * When the attribute is global, uses straight the attribute options.
	 * Otherwise, uses the (mapped) attribute terms.
	 */
	const attributeTerms =
		( isGlobalAttribute
			? attribute.options?.map( stringToTokenItem )
			: attribute?.terms?.map( ( { name } ) =>
					stringToTokenItem( name )
			  ) ) || [];

	// Combine the temporary terms with the selected values.
	const tokenFieldValues: TokenItem[] = [
		...( attributeTerms || [] ),
		...temporaryTerms,
	];

	// Flag to track if the terms are initially populated.
	const [ initiallyPopulated, setInitiallyPopulated ] = useState( false );

	// Auto select terms based on the termsAutoSelection prop.
	useEffect( () => {
		// If the terms are not set, bail early.
		if ( ! termsAutoSelection ) {
			return;
		}

		// If the terms are already populated, bail early.
		if ( initiallyPopulated ) {
			return;
		}

		// If the attribute is not set, bail early.
		if ( ! attribute ) {
			return;
		}

		// If the terms are not loaded, bail early.
		if ( ! terms?.length ) {
			return;
		}

		// Set the flag to true.
		setInitiallyPopulated( true );

		/*
		 * If terms auto selection is set to 'first',
		 * and there are terms, select the first term,
		 * and bail early.
		 */
		if ( termsAutoSelection === 'first' ) {
			return onTermsSelect( [ terms[ 0 ] ], index, attribute );
		}

		// auto select all terms
		onTermsSelect( terms, index, attribute );
	}, [
		termsAutoSelection,
		initiallyPopulated,
		attribute,
		terms,
		onTermsSelect,
		index,
	] );

	/*
	 * Filter the attributes to exclude
	 * attributes that are already taken,
	 * less the current attribute.
	 */
	const filteredAttributes = attributes?.filter( ( item ) => {
		return (
			item.id === attributeId ||
			( typeof item?.takenBy !== 'undefined' ? item.takenBy < 0 : true )
		);
	} );

	async function addNewTerms(
		newTerms: TokenItem[],
		selectedTerms: ProductAttributeTerm[]
	) {
		if ( ! attribute ) {
			return;
		}

		/*
		 * Create the temporary terms.
		 * It will show the tokens in the token field
		 * optimistically, before the terms are created.
		 */
		setTemporaryTerms( ( prevTerms ) => [ ...prevTerms, ...newTerms ] );

		// Create the new terms.
		const promises = newTerms.map( async ( term ) => {
			const newTerm = ( await createProductAttributeTerm(
				{
					name: term.value,
					slug: cleanForSlug( term.value ),
					attribute_id: attributeId,
				},
				{
					optimisticQueryUpdate: {
						search: '',
						attribute_id: attributeId,
					},
					optimisticUrlParameters: [ attributeId ],
				}
			) ) as ProductAttributeTerm;

			return newTerm;
		} );

		const newItems = await Promise.all( promises );

		// Clean up temporary terms
		setTemporaryTerms( [] );

		onTermsSelect( [ ...selectedTerms, ...newItems ], index, attribute );
	}

	/*
	 * Check if there are available suggestions
	 * to show the values column,
	 * comparing the suggestions length with the selected values length.
	 */
	const hasAvailableSuggestions =
		tokenFieldSuggestions?.length &&
		tokenFieldSuggestions.length > ( tokenFieldValues?.length || 0 );

	return (
		<tr
			key={ index }
			className={ `woocommerce-new-attribute-modal__table-row woocommerce-new-attribute-modal__table-row-${ index }` }
		>
			<td className="woocommerce-new-attribute-modal__table-attribute-column">
				<AttributesComboboxControl
					instanceNumber={ index }
					placeholder={ attributePlaceholder }
					current={ attribute }
					items={ filteredAttributes }
					isLoading={ isLoadingAttributes }
					onAddNew={ ( newValue ) =>
						onNewAttributeAdd?.( newValue, index )
					}
					onChange={ ( nextAttribute ) => {
						if ( nextAttribute.id === attributeId ) {
							return;
						}

						onAttributeSelect( nextAttribute, index );
						setInitiallyPopulated( false );
					} }
					disabledAttributeMessage={ disabledAttributeMessage }
				/>
			</td>

			<td
				className={ `woocommerce-new-attribute-modal__table-attribute-value-column${
					hasAvailableSuggestions ? ' has-values' : ''
				}` }
			>
				<FormTokenField
					placeholder={ termPlaceholder }
					disabled={ ! attribute }
					suggestions={ tokenFieldSuggestions }
					value={ tokenFieldValues }
					onChange={ (
						nextSelectedTerms: ( TokenItem | string )[]
					) => {
						if ( ! attribute ) {
							return;
						}

						/*
						 * Create a new strings array with the new selected terms,
						 * used to pass to the Form component.
						 */
						const nextStringTerms =
							nextSelectedTerms.map( tokenItemToString );

						/*
						 * Create an array with the new terms to add,
						 * filtering the new selected terms that are not in the
						 * suggestions array.
						 */
						const newItems = nextStringTerms
							.filter(
								( t ) => ! tokenFieldSuggestions.includes( t )
							)
							.map( stringToTokenItem );

						const selectedTerms = isGlobalAttribute
							? nextStringTerms
							: terms?.filter( ( term ) =>
									nextStringTerms.includes( term.name )
							  );

						// Call the callback to update the Form terms.
						onTermsSelect( selectedTerms, index, attribute );

						// If it is a global attribute, set the local terms.
						if ( isGlobalAttribute ) {
							return setLocalTerms( ( prevTerms ) => [
								...prevTerms,
								...newItems,
							] );
						}

						/*
						 * Create new terms, in case there are any,
						 * when it is not a global attribute.
						 */
						if ( newItems.length ) {
							addNewTerms(
								newItems.map( ( item ) => ( {
									...item,
									status: 'validating',
								} ) ),
								selectedTerms as ProductAttributeTerm[]
							);
						}
					} }
					__experimentalExpandOnFocus={ true }
					__experimentalAutoSelectFirstMatch={ true }
				/>
			</td>
			<td className="woocommerce-new-attribute-modal__table-attribute-trash-column">
				<Button
					icon={ closeSmall }
					disabled={ clearButtonDisabled }
					label={ removeLabel }
					onClick={ () => onRemove( index ) }
				></Button>
			</td>
		</tr>
	);
};
