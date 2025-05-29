/**
 * External dependencies
 */
import type {
	ValidationData,
	ValidationContextError,
} from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import deprecated from '@wordpress/deprecated';

let warned = false;

const warnDeprecation = () => {
	if ( ! warned ) {
		deprecated( 'useValidation()', {
			alternative: 'validationStore with useSelect/useDispatch',
			plugin: 'WooCommerce',
			hint: 'Access the validation store directly in your component. See: https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/data-store/validation.md',
		} );
		warned = true;
	}
};

/**
 * @deprecated useValidation is deprecated.
 * Use validationStore directly with useSelect and useDispatch.
 */
export const useValidation = (): ValidationData => {
	const { clearValidationError, hideValidationError, setValidationErrors } =
		useDispatch( validationStore );

	const prefix = 'extensions-errors';

	const { hasValidationErrors, getValidationErrorSelector } = useSelect(
		( select ) => {
			const store = select( validationStore );
			return {
				hasValidationErrors: store.hasValidationErrors(),
				getValidationErrorSelector: store.getValidationError,
			};
		},
		[]
	);

	return {
		get hasValidationErrors() {
			warnDeprecation();
			return hasValidationErrors;
		},
		get getValidationError() {
			warnDeprecation();
			return ( validationErrorId: string ) =>
				getValidationErrorSelector(
					`${ prefix }-${ validationErrorId }`
				);
		},
		get clearValidationError() {
			warnDeprecation();
			return ( validationErrorId: string ) =>
				clearValidationError( `${ prefix }-${ validationErrorId }` );
		},
		get hideValidationError() {
			warnDeprecation();
			return ( validationErrorId: string ) =>
				hideValidationError( `${ prefix }-${ validationErrorId }` );
		},
		get setValidationErrors() {
			warnDeprecation();
			return ( errorsObject: Record< string, ValidationContextError > ) =>
				setValidationErrors(
					Object.fromEntries(
						Object.entries( errorsObject ).map(
							( [ validationErrorId, error ] ) => [
								`${ prefix }-${ validationErrorId }`,
								error,
							]
						)
					)
				);
		},
	};
};
