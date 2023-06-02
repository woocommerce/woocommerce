/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
import { SelectControl } from '@wordpress/components';
import { useValidationContext } from '@woocommerce/base-context';
import { useEffect } from 'react';
import classnames from 'classnames';
import { ValidationInputError } from '@woocommerce/base-components/validation';

// Default option for select boxes.
const selectAnOption = {
	value: '',
	label: __( 'Select an option', 'woocommerce' ),
};

/**
 * VariationAttributeSelect component.
 *
 * @param {*} props Component props.
 */
const AttributeSelectControl = ( {
	attributeName,
	options = [],
	value = '',
	onChange = () => {},
	errorMessage = __(
		'Please select a value.',
		'woocommerce'
	),
} ) => {
	const {
		getValidationError,
		setValidationErrors,
		clearValidationError,
	} = useValidationContext();
	const errorId = attributeName;
	const error = getValidationError( errorId ) || {};

	useEffect( () => {
		if ( value ) {
			clearValidationError( errorId );
		} else {
			setValidationErrors( {
				[ errorId ]: {
					message: errorMessage,
					hidden: true,
				},
			} );
		}
	}, [
		value,
		errorId,
		errorMessage,
		clearValidationError,
		setValidationErrors,
	] );

	// Remove validation errors when unmounted.
	useEffect( () => () => void clearValidationError( errorId ), [
		errorId,
		clearValidationError,
	] );

	return (
		<div className="wc-block-components-product-add-to-cart-attribute-picker__container">
			<SelectControl
				label={ decodeEntities( attributeName ) }
				value={ value || '' }
				options={ [ selectAnOption, ...options ] }
				onChange={ onChange }
				required={ true }
				className={ classnames(
					'wc-block-components-product-add-to-cart-attribute-picker__select',
					{
						'has-error': error.message && ! error.hidden,
					}
				) }
			/>
			<ValidationInputError
				propertyName={ errorId }
				elementId={ errorId }
			/>
		</div>
	);
};

export default AttributeSelectControl;
