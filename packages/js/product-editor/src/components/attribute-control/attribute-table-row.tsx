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

export const AttributeTableRow: React.FC< AttributeTableRowProps > = ( {
	index,
	attribute,
	attributePlaceholder,
	disabledAttributeMessage,
	isLoadingAttributes,
	attributes,
	onAttributeSelect,

	termLabel = undefined,
	termPlaceholder,
	onTermsSelect,

	termsAutoSelection,

	clearButtonDisabled,
	removeLabel,
	onRemove,
} ) => {
	const attributeId = attribute ? attribute.id : undefined;

	const { createProductAttributeTerm, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME );

	const { terms } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getProductAttributeTerms } = select(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			);

			return {
				terms: attributeId
					? ( getProductAttributeTerms( {
							search: '',
							attribute_id: attributeId,
					  } ) as ProductAttributeTerm[] )
					: [],
			};
		},
		[ attributeId ]
	);

	/**
	 * Local terms to handle not global attributes.
	 * Set initially with the attribute options.
	 */
	const [ localTerms, setLocalTerms ] = useState< string[] | undefined >(
		attribute?.options
	);

	// By convention, it's a global attribute if the attribute ID is 0.
	const isGlobalAttribute = attribute?.id === 0;

	/*
	 * Set initially the the FormTokenField suggestions
	 * with the attribute options (localTerms), but
	 * if it's not a global attribute
	 * set the suggestions with the terms names.
	 */
	const suggestions = isGlobalAttribute
		? localTerms
		: terms?.map( ( term: ProductAttributeTerm ) => term.name );

	/*
	 * Build selected options object from the attribute,
	 * used to populate the token field.
	 * When the attribute is global, uses straigh the attribute options.
	 * Otherwise, maps the terms to their names.
	 */
	const selectedValues = isGlobalAttribute
		? attribute.options
		: attribute?.terms?.map( ( option ) => option.name );

	// Flag to track if the terms are initially populated.
	const [ initiallyPopulated, setInitiallyPopulated ] = useState( false );

	/*
	 * Auto select terms based on the termsAutoSelection prop.
	 */
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
		termNames: string[],
		selectedTerms: ProductAttributeTerm[]
	) {
		if ( ! attribute ) {
			return;
		}

		// Create the new terms.
		const promises = termNames.map( async ( termName ) => {
			const newTerm = ( await createProductAttributeTerm( {
				name: termName,
				slug: cleanForSlug( termName ),
				attribute_id: attributeId,
			} ) ) as ProductAttributeTerm;

			return newTerm;
		} );

		const newItems = await Promise.all( promises );

		/*
		 * Refresh attribute terms, invalidating the resolution
		 * to include the newly created terms.
		 * ToDo: Implement it optimally.
		 */
		invalidateResolutionForStoreSelector( 'getProductAttributeTerms', [
			{ search: '', attribute_id: attributeId },
		] );

		onTermsSelect( [ ...selectedTerms, ...newItems ], index, attribute );
	}

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
					// eslint-disable-next-line jsx-a11y/no-autofocus
					autoFocus={ true }
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

			<td className="woocommerce-new-attribute-modal__table-attribute-value-column">
				<FormTokenField
					label={ termLabel }
					placeholder={ termPlaceholder }
					disabled={ ! attribute }
					suggestions={ suggestions }
					value={ selectedValues }
					onChange={ ( newSelectedTerms: string[] ) => {
						if ( ! attribute ) {
							return;
						}

						/*
						 * Extract new terms (string[]) from the selected terms,
						 * by extracting the terms that are not in the suggestions.
						 * If the new terms are not in the suggestions, they are new ones.
						 */
						const newStringTerms = newSelectedTerms.filter(
							( term ) => ! suggestions?.includes( term )
						);

						/*
						 * Selected terms are the selected ones when it is a global attribute,
						 * otherwise, the selected terms are the terms that are in ProductAttributeTerm[].
						 */
						const selectedTerms = isGlobalAttribute
							? newSelectedTerms
							: terms.filter( ( term ) =>
									newSelectedTerms.includes( term.name )
							  );

						onTermsSelect( selectedTerms, index, attribute );

						// If it is a global attribute, set the local terms.
						if ( isGlobalAttribute ) {
							return setLocalTerms( ( prevTerms = [] ) => [
								...prevTerms,
								...newStringTerms,
							] );
						}

						/*
						 * Create new terms, in case there are any,
						 * when it is not a global attribute.
						 */
						if ( newStringTerms.length ) {
							addNewTerms(
								newStringTerms,
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
