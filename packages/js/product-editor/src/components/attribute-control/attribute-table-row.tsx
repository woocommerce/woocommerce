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
import { useSelect } from '@wordpress/data';
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttributeTerm,
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
	onTermSelect,

	termsAutoSelection,

	clearButtonDisabled,
	removeLabel,
	onRemove,
} ) => {
	const attributeId = attribute ? attribute.id : undefined;

	const { terms } = useSelect(
		// eslint-disable-next-line @typescript-eslint/ban-ts-comment
		// @ts-ignore
		( select: WCDataSelector ) => {
			const { getProductAttributeTerms, hasFinishedResolution } = select(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			);

			if ( ! attributeId ) {
				return {
					terms: [],
					hasLoadedTerms: true,
				};
			}

			return {
				terms: getProductAttributeTerms( {
					search: '',
					attribute_id: attributeId,
				} ) as ProductAttributeTerm[],
				hasLoadedTerms: hasFinishedResolution,
			};
		},
		[ attributeId ]
	);

	const attributeTermPropName: 'terms' | 'options' = 'terms';

	// Memoize the terms.
	const memoizedTerms = useMemo( () => terms, [ terms ] );

	// Map the terms to suggestions to populate the token field.
	const suggestions = terms
		? terms.map( ( term: ProductAttributeTerm ) => term.name )
		: undefined;

	// Get the selected options.
	const selectedOptions = attribute?.[ attributeTermPropName ]?.map(
		( option ) => option.name
	);

	// The field name for the terms.
	const termsFieldName = `attributes[${ index }].${ attributeTermPropName }`;

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
		if ( ! memoizedTerms ) {
			return;
		}

		/*
		 * If terms auto selection is set to 'first',
		 * and there are terms,
		 * select the first term.
		 */
		if ( termsAutoSelection === 'first' && memoizedTerms.length ) {
			return onTermSelect( termsFieldName, [ memoizedTerms[ 0 ] ] );
		}

		// auto select all terms
		onTermSelect( termsFieldName, memoizedTerms );

		// Set the flag to true.
		setInitiallyPopulated( true );
	}, [
		termsAutoSelection,
		initiallyPopulated,
		attribute,
		memoizedTerms,
		onTermSelect,
		termsFieldName,
	] );

	return (
		<tr
			key={ index }
			className={ `woocommerce-new-attribute-modal__table-row woocommerce-new-attribute-modal__table-row-${ index }` }
		>
			<td className="woocommerce-new-attribute-modal__table-attribute-column">
				<AttributesComboboxControl
					placeholder={ attributePlaceholder }
					current={ attribute }
					items={ attributes }
					isLoading={ isLoadingAttributes }
					onChange={ ( nextAttribute ) => {
						if ( nextAttribute.id === attribute?.id ) {
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
					value={ selectedOptions }
					onChange={ ( stringTerms ) => {
						const selectedterms = terms.filter( ( term ) =>
							stringTerms.includes( term.name )
						);

						onTermSelect( termsFieldName, selectedterms );
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
