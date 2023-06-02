/**
 * External dependencies
 */
import { useState, useEffect, useMemo } from '@wordpress/element';
import { useShallowEqual } from '@woocommerce/base-hooks';

/**
 * Internal dependencies
 */
import AttributeSelectControl from './attribute-select-control';
import {
	getVariationMatchingSelectedAttributes,
	getActiveSelectControlOptions,
} from './utils';

/**
 * AttributePicker component.
 *
 * @param {*} props Component props.
 */
const AttributePicker = ( {
	attributes,
	variationAttributes,
	setRequestParams,
} ) => {
	const currentAttributes = useShallowEqual( attributes );
	const currentVariationAttributes = useShallowEqual( variationAttributes );
	const [ variationId, setVariationId ] = useState( 0 );
	const [ selectedAttributes, setSelectedAttributes ] = useState( {} );

	// Get options for each attribute picker.
	const filteredAttributeOptions = useMemo( () => {
		return getActiveSelectControlOptions(
			currentAttributes,
			currentVariationAttributes,
			selectedAttributes
		);
	}, [ selectedAttributes, currentAttributes, currentVariationAttributes ] );

	// Select variations when selections are change.
	useEffect( () => {
		const hasSelectedAllAttributes =
			Object.values( selectedAttributes ).filter(
				( selected ) => selected !== ''
			).length === Object.keys( currentAttributes ).length;

		if ( hasSelectedAllAttributes ) {
			setVariationId(
				getVariationMatchingSelectedAttributes(
					currentAttributes,
					currentVariationAttributes,
					selectedAttributes
				)
			);
		} else if ( variationId > 0 ) {
			// Unset variation when form is incomplete.
			setVariationId( 0 );
		}
	}, [
		selectedAttributes,
		variationId,
		currentAttributes,
		currentVariationAttributes,
	] );

	// Set requests params as variation ID and data changes.
	useEffect( () => {
		setRequestParams( {
			id: variationId,
			variation: Object.keys( selectedAttributes ).map(
				( attributeName ) => {
					return {
						attribute: attributeName,
						value: selectedAttributes[ attributeName ],
					};
				}
			),
		} );
	}, [ setRequestParams, variationId, selectedAttributes ] );

	return (
		<div className="wc-block-components-product-add-to-cart-attribute-picker">
			{ Object.keys( currentAttributes ).map( ( attributeName ) => (
				<AttributeSelectControl
					key={ attributeName }
					attributeName={ attributeName }
					options={ filteredAttributeOptions[ attributeName ] }
					value={ selectedAttributes[ attributeName ] }
					onChange={ ( selected ) => {
						setSelectedAttributes( {
							...selectedAttributes,
							[ attributeName ]: selected,
						} );
					} }
				/>
			) ) }
		</div>
	);
};

export default AttributePicker;
