/**
 * External dependencies
 */
import {
	createElement,
	useEffect,
	useMemo,
	useState,
} from '@wordpress/element';
import { closeSmall } from '@wordpress/icons';
import {
	Button,
	FormTokenField as CoreFormTokenField,
} from '@wordpress/components';
import { useSelect, useDispatch, select as sel } from '@wordpress/data';
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
	__experimentalShowHowTo?: boolean;
	placeholder: string;
	label?: string;
}
const FormTokenField =
	CoreFormTokenField as React.ComponentType< FormTokenFieldProps >;

/*
 * Type copied from core FormTokenField component.
 * Todo: move to a shared location.
 */
interface TokenItem {
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
 * Convert a string or a TokenItem to a string.
 *
 * @param {string | TokenItem} item - The item to convert.
 * @return {string} The string.
 */
const tokenItemToString = ( item: string | TokenItem ): string =>
	typeof item === 'string' ? item : item.value;

const INITIAL_MAX_TOKENS_TO_SHOW = 20;
const MAX_TERMS_TO_LOAD = 100;

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
	const selectItemsQuery = useMemo(
		() => ( {
			search: '',
			attribute_id: attributeId,
			per_page: MAX_TERMS_TO_LOAD, // @todo: handle this by using `search` arg
		} ),
		[ attributeId ]
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
				? ( getProductAttributeTerms(
						selectItemsQuery
				  ) as ProductAttributeTerm[] )
				: [];
		},
		[ attributeId, selectItemsQuery ]
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

		// auto select the first INITIAL_MAX_TOKENS_TO_SHOW terms
		onTermsSelect(
			terms.slice( 0, INITIAL_MAX_TOKENS_TO_SHOW ),
			index,
			attribute
		);
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

	async function addNewTerms( newTokens: TokenItem[] ) {
		if ( ! attribute ) {
			return;
		}

		/*
		 * Create the temporary terms.
		 * It will show the tokens in the token field
		 * optimistically, before the terms are created.
		 */
		setTemporaryTerms( ( prevTerms ) => [ ...prevTerms, ...newTokens ] );

		// Create the new terms.
		const promises = newTokens.map( async ( token ) => {
			const newTerm = ( await createProductAttributeTerm(
				{
					name: token.value,
					slug: cleanForSlug( token.value ),
					attribute_id: attributeId,
				},
				{
					optimisticQueryUpdate: selectItemsQuery,
					optimisticUrlParameters: [ attributeId ],
				}
			) ) as ProductAttributeTerm;

			return newTerm;
		} );

		const newTerms = await Promise.all( promises );

		// Remove the recently created terms from the temporary state,
		setTemporaryTerms( ( prevTerms ) =>
			prevTerms.filter( ( term ) => ! newTokens.includes( term ) )
		);

		/*
		 * Pull the recent terms list from the store
		 * to get the terms that were just created.
		 * @Todo: using this `sel` alias is a workaround.
		 * The optimistic rendering should be implemented in the store.
		 */
		const recentTermsList = sel(
			EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
		).getProductAttributeTerms(
			selectItemsQuery
		) as ProductAttributeTerm[];

		/*
		 * New selected terms are the ones that are in the recent terms list
		 * and also in the token field values.
		 */
		const newSelectedTerms = recentTermsList.filter( ( term ) =>
			tokenFieldValues.map( ( item ) => item.value ).includes( term.name )
		);

		// Call the callback to update the Form terms.
		onTermsSelect( [ ...newSelectedTerms, ...newTerms ], index, attribute );
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
					onChange={ ( nextTokens: ( TokenItem | string )[] ) => {
						if ( ! attribute ) {
							return;
						}

						// Create a new strings array with the new selected tokens,
						const nextStringTokens =
							nextTokens.map( tokenItemToString );

						/*
						 * Create an array with the new tokens to add,
						 * filtering the new selected ones that are not in the
						 * suggestions array.
						 */
						const newTokens = nextStringTokens
							.filter(
								( t ) => ! tokenFieldSuggestions.includes( t )
							)
							.map( stringToTokenItem );

						// Selected Terms to pass to the Form.
						const selectedTerms = isGlobalAttribute
							? nextStringTokens
							: terms?.filter( ( term ) =>
									nextStringTokens.includes( term.name )
							  );

						// Call the callback to update the Form terms.
						onTermsSelect( selectedTerms, index, attribute );

						// If it is a global attribute, set the local terms.
						if ( isGlobalAttribute ) {
							return setLocalTerms( ( prevTerms ) => [
								...prevTerms,
								...newTokens,
							] );
						}

						/*
						 * Create new terms, in case there are any,
						 * when it is not a global attribute.
						 */
						if ( newTokens.length ) {
							addNewTerms(
								newTokens.map( ( item ) => ( {
									...item,
									status: 'validating',
								} ) )
							);
						}
					} }
					__experimentalExpandOnFocus={ true }
					__experimentalAutoSelectFirstMatch={ true }
					__experimentalShowHowTo={ true }
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
