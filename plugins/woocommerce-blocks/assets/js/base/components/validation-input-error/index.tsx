/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { VALIDATION_STORE_KEY } from '@woocommerce/block-data';

/**
 * Internal dependencies
 */
import './style.scss';

interface ValidationInputErrorProps {
	errorMessage?: string;
	propertyName?: string;
	elementId?: string;
}

export const ValidationInputError = ( {
	errorMessage = '',
	propertyName = '',
	elementId = '',
}: ValidationInputErrorProps ): JSX.Element | null => {
	const { getValidationError, getValidationErrorId } = useSelect(
		( select ) => {
			const store = select( VALIDATION_STORE_KEY );
			return {
				getValidationError: store.getValidationError(),
				getValidationErrorId: store.getValidationErrorId(),
			};
		}
	);
	const validationError = getValidationError( propertyName );

	if ( ! errorMessage || typeof errorMessage !== 'string' ) {
		const error = validationError || {};
		if ( error.message && ! error.hidden ) {
			errorMessage = error.message;
		} else {
			return null;
		}
	}

	return (
		<div className="wc-block-components-validation-error" role="alert">
			<p id={ getValidationErrorId( elementId ) }>{ errorMessage }</p>
		</div>
	);
};

export default ValidationInputError;
