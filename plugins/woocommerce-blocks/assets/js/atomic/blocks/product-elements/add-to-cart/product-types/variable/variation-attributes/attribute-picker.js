/**
 * External dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AttributeSelectControl from './attribute-select-control';
import {
	getVariationsMatchingSelectedAttributes,
	getSelectControlOptions,
} from './utils';

/**
 * AttributePicker component.
 *
 * @param {*} props Component props.
 */
const AttributePicker = ( { attributes, variationAttributes } ) => {
	const [ variationId, setVariationId ] = useState( 0 );

	// @todo Support default selected attributes in Variation Picker.
	const [ selectedAttributes, setSelectedAttributes ] = useState( [] );

	const attributeNames = Object.keys( attributes );
	const hasSelectedAttributes =
		Object.values( selectedAttributes ).filter( Boolean ).length > 0;
	const hasSelectedAllAttributes =
		Object.values( selectedAttributes ).filter(
			( selected ) => selected !== ''
		).length === attributeNames.length;

	// Gets valid attribute options for the picker taking current selections into account.
	const filteredAttributeOptions = useMemo( () => {
		const options = [];

		attributeNames.forEach( ( attributeName ) => {
			const currentAttribute = attributes[ attributeName ];
			const attributeNamesExcludingCurrentAttribute = attributeNames.filter(
				( name ) => name !== attributeName
			);
			const matchingVariationIds = hasSelectedAttributes
				? getVariationsMatchingSelectedAttributes( {
						selectedAttributes,
						variationAttributes,
						attributeNames: attributeNamesExcludingCurrentAttribute,
				  } )
				: null;
			const validAttributeTerms =
				matchingVariationIds !== null
					? matchingVariationIds.map(
							( varId ) =>
								variationAttributes[ varId ][ attributeName ]
					  )
					: null;
			options[ attributeName ] = getSelectControlOptions(
				currentAttribute.terms,
				validAttributeTerms
			);
		} );

		return options;
	}, [
		attributes,
		variationAttributes,
		attributeNames,
		selectedAttributes,
		hasSelectedAttributes,
	] );

	// Select variation when selections change.
	useEffect( () => {
		if ( ! hasSelectedAllAttributes ) {
			setVariationId( 0 );
			return;
		}

		const matchingVariationIds = getVariationsMatchingSelectedAttributes( {
			selectedAttributes,
			variationAttributes,
			attributeNames,
		} );

		setVariationId( matchingVariationIds[ 0 ] || 0 );
	}, [
		selectedAttributes,
		variationAttributes,
		attributeNames,
		hasSelectedAllAttributes,
	] );

	// @todo Hook up Variation Picker with Cart Form.
	return (
		<div className="wc-block-components-product-add-to-cart-attribute-picker">
			{ attributeNames.map( ( attributeName ) => (
				<AttributeSelectControl
					key={ attributeName }
					attributeName={ attributeName }
					options={ filteredAttributeOptions[ attributeName ] }
					selected={ selectedAttributes[ attributeName ] }
					onChange={ ( selected ) => {
						setSelectedAttributes( {
							...selectedAttributes,
							[ attributeName ]: selected,
						} );
					} }
				/>
			) ) }
			<p>Matched variation ID: { variationId }</p>
		</div>
	);
};

export default AttributePicker;
