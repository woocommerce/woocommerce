/**
 * External dependencies
 */
import { useCallback } from '@wordpress/element';
import type {
	ValidationData,
	ValidationContextError,
} from '@woocommerce/types';
import { useDispatch, useSelect } from '@wordpress/data';
import { validationStore } from '@woocommerce/block-data';
import deprecated from '@wordpress/deprecated';

let deprecationNoticeShown = false;

const showDeprecationNotice = () => {
	if ( ! deprecationNoticeShown ) {
		deprecated( 'useValidation()', {
			since: '10.0',
			alternative: 'the validation data store',
			plugin: 'WooCommerce',
			hint: 'Access the validation store directly in your component. \nSee: https://github.com/woocommerce/woocommerce/blob/trunk/plugins/woocommerce/client/blocks/docs/third-party-developers/extensibility/data-store/validation.md \nSee: https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/',
		} );
		deprecationNoticeShown = true;
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

	const {
		hasValidationErrors,
		getValidationError,
	}: {
		hasValidationErrors: boolean;
		getValidationError: (
			validationErrorId: string
		) => ValidationContextError;
	} = useSelect(
		( select ) => {
			const store = select( validationStore );
			return {
				hasValidationErrors: store.hasValidationErrors(),
				getValidationError: ( validationErrorId: string ) =>
					store.getValidationError(
						`${ prefix }-${ validationErrorId }`
					),
			};
		},
		[ prefix ]
	);

	const clearValidationErrorCallback = useCallback(
		( validationErrorId: string ) =>
			clearValidationError( `${ prefix }-${ validationErrorId }` ),
		[ clearValidationError, prefix ]
	);

	const hideValidationErrorCallback = useCallback(
		( validationErrorId: string ) =>
			hideValidationError( `${ prefix }-${ validationErrorId }` ),
		[ hideValidationError, prefix ]
	);

	const setValidationErrorsCallback = useCallback(
		( errorsObject: Record< string, ValidationContextError > ) =>
			setValidationErrors(
				Object.fromEntries(
					Object.entries( errorsObject ).map(
						( [ validationErrorId, error ] ) => [
							`${ prefix }-${ validationErrorId }`,
							error,
						]
					)
				)
			),
		[ setValidationErrors, prefix ]
	);

	return {
		get hasValidationErrors() {
			showDeprecationNotice();
			return hasValidationErrors;
		},
		get getValidationError() {
			showDeprecationNotice();
			return getValidationError;
		},
		get clearValidationError() {
			showDeprecationNotice();
			return clearValidationErrorCallback;
		},
		get hideValidationError() {
			showDeprecationNotice();
			return hideValidationErrorCallback;
		},
		get setValidationErrors() {
			showDeprecationNotice();
			return setValidationErrorsCallback;
		},
	};
};
