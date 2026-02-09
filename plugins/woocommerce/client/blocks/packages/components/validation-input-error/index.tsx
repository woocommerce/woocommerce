/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { Icon, warning } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import './style.scss';

export interface ValidationInputErrorProps {
	errorMessage?: string;
	propertyName?: string;
	elementId?: string;
}

export const ValidationInputError = ( {
	errorMessage = '',
	propertyName = '',
	elementId = '',
}: ValidationInputErrorProps ): JSX.Element | null => {
	const { validationError, validationErrorId } = useSelect(
		( select ) => {
			const store = select( validationStore );

			return {
				validationError: store.getValidationError( propertyName ),
				validationErrorId: store.getValidationErrorId( elementId ),
			};
		},
		[ propertyName, elementId ]
	);

	if ( ! errorMessage || typeof errorMessage !== 'string' ) {
		if ( validationError?.message && ! validationError?.hidden ) {
			errorMessage = validationError.message;
		} else {
			return null;
		}
	}

	return (
		<div className="wc-block-components-validation-error" role="alert">
			<p id={ validationErrorId }>
				{ /* @ts-expect-error - TS wants the Icon component to define svg specific props, but it's not always SVG */ }
				<Icon icon={ warning } />
				<span>{ errorMessage }</span>
			</p>
		</div>
	);
};

export default ValidationInputError;
