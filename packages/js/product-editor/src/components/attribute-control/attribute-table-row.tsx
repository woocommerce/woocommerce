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

	const attributeTermPropName: 'terms' | 'options' = 'terms';

	// Map the terms to suggestions to populate the token field.
	const suggestions = terms
		? terms.map( ( term: ProductAttributeTerm ) => term.name )
		: undefined;

	/*
	 * Get the selected options,
	 * used to populate the token field.
	 */
	const selectedValues = attribute?.[ attributeTermPropName ]?.map(
		( option ) => option.name
	);

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
		itemsSelection: ProductAttributeTerm[]
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

		onTermsSelect( [ ...itemsSelection, ...newItems ], index, attribute );
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
					onChange={ ( stringTerms: string[] ) => {
						if ( ! attribute ) {
							return;
						}

						// Extract new terms from the terms (string[])
						const newStringTerms = stringTerms.filter(
							( term ) => ! suggestions?.includes( term )
						);

						// Get the current selected terms.
						const itemsSelection = terms.filter( ( term ) =>
							stringTerms.includes( term.name )
						);

						// Select the terms
						onTermsSelect( itemsSelection, index, attribute );

						// Create new terms, in case there are any.
						if ( newStringTerms.length ) {
							return addNewTerms(
								newStringTerms,
								itemsSelection
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
