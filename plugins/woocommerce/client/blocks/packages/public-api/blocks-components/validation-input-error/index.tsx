/**
 * External dependencies
 */
import clsx from 'clsx';
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
		if ( validationError?.message && ! validationError?.hidden ) {
			errorMessage = validationError.message;
		} else {
			errorMessage = '';
		}
	}

	// Also enters when its field mounts with it, and keeps its last message, hidden from assistive tech, as it leaves.
	const presence = usePresence( !! errorMessage, {
		data: errorMessage,
		enterOnMount: true,
		layout: true,
	} );
	if ( ! presence.isPresent ) {
		return null;
	}

	return (
		<div
			ref={ presence.ref }
			className={ clsx(
				'wc-block-components-validation-error',
				presence.className
			) }
			role="alert"
			aria-hidden={ errorMessage ? undefined : true }
		>
			<p id={ errorMessage ? validationErrorId : undefined }>
				<Icon icon={ cautionFilled } />
				<span>{ presence.data }</span>
			</p>
		</div>
	);
};

export default ValidationInputError;
