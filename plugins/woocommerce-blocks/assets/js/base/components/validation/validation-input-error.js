/**
 * External dependencies
 */
import { useValidationContext } from '@woocommerce/base-context';
import PropTypes from 'prop-types';

/**
 * Internal dependencies
 */
import './style.scss';

export const ValidationInputError = ( {
	errorMessage = '',
	propertyName = '',
	elementId = '',
} ) => {
	const { getValidationError, getValidationErrorId } = useValidationContext();
	if ( ! errorMessage && ! propertyName ) {
		return null;
	}
	errorMessage = errorMessage || getValidationError( propertyName );
	return (
		<div className="wc-block-form-input-validation-error" role="alert">
			<p id={ getValidationErrorId( elementId ) }>{ errorMessage }</p>
		</div>
	);
};

ValidationInputError.propTypes = {
	errorMessage: PropTypes.string,
	propertyName: PropTypes.string,
	elementId: PropTypes.string,
};
