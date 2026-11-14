/**
 * External dependencies
 */
import { useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import { usePresence } from '@woocommerce/base-hooks';
import { Icon, cautionFilled } from '@wordpress/icons';

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
		errorMessage =
			validationError?.message && ! validationError?.hidden
				? validationError.message
				: '';
	}

	// The error grows in and collapses out, so the content below moves with it. Errors that the page shows before the
	// shopper interacts, such as for invalid saved data, appear immediately.
	const presence = usePresence( errorMessage || null, {
		animate: navigator.userActivation?.hasBeenActive !== false,
		collapse: true,
	} );

	if ( ! presence.value ) {
		return null;
	}

	return (
		<div
			ref={ presence.ref }
			className="wc-block-components-validation-error"
			role="alert"
			// The error is already gone for the shopper while it collapses. React 18 has no `inert` prop.
			{ ...( presence.isExiting && { inert: '', 'aria-hidden': true } ) }
		>
			<p
				// A new paragraph when the error comes back during its collapse, so the alert announces it again.
				key={ presence.isExiting ? 'exiting' : 'present' }
				id={ presence.isExiting ? undefined : validationErrorId }
			>
				<Icon icon={ cautionFilled } />
				<span>{ presence.value }</span>
			</p>
		</div>
	);
};

export default ValidationInputError;
