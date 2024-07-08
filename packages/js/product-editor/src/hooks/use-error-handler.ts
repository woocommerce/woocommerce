/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback } from '@wordpress/element';
import { getNewPath, navigateTo } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import { useValidations } from '../contexts/validation-context';

export type WPErrorCode =
	| 'variable_product_no_variation_prices'
	| 'product_form_field_error'
	| 'product_invalid_sku'
	| 'product_invalid_global_unique_id'
	| 'product_create_error'
	| 'product_publish_error'
	| 'product_preview_error';

export type WPError = {
	code: WPErrorCode;
	message: string;
	validatorId?: string;
	context?: string;
};

type ErrorProps = {
	explicitDismiss: boolean;
	actions?: ErrorAction[];
};

type ErrorAction = {
	label: string;
	onClick: () => void;
};

type UseErrorHandlerTypes = {
	getProductErrorMessageAndProps: (
		error: WPError,
		visibleTab: string | null
	) => {
		message: string;
		errorProps: ErrorProps;
	};
};

function getUrl( tab: string ): string {
	return getNewPath( { tab } );
}

function getErrorPropsWithActions(
	errorContext = '',
	validatorId: string,
	focusByValidatiorId: ( validatorId: string ) => void
): ErrorProps {
	return {
		explicitDismiss: true,
		actions: [
			{
				label: 'View error',
				onClick: () => {
					navigateTo( {
						url: getUrl( errorContext ),
					} );
					// focusByValidatiorId( validatorId );
					setTimeout( () => {
						focusByValidatiorId( validatorId );
					}, 100 );
				},
			},
		],
	};
}

export const useErrorHandler = (): UseErrorHandlerTypes => {
	const { focusByValidatiorId } = useValidations();

	const getProductErrorMessageAndProps = useCallback(
		( error: WPError, visibleTab: string | null ) => {
			const response = {
				message: '',
				errorProps: {} as ErrorProps,
			};
			const {
				code,
				context: errorContext,
				message: errorMessage,
				validatorId = '',
			} = error;
			switch ( code ) {
				case 'variable_product_no_variation_prices':
					response.message = errorMessage;
					if ( visibleTab !== 'variations' ) {
						response.errorProps = getErrorPropsWithActions(
							errorContext,
							validatorId,
							focusByValidatiorId
						);
					}
					break;
				case 'product_form_field_error':
					response.message = errorMessage;
					if ( visibleTab !== 'general' ) {
						response.errorProps = getErrorPropsWithActions(
							errorContext,
							validatorId,
							focusByValidatiorId
						);
					}
					break;
				case 'product_invalid_sku':
					response.message = __(
						'Invalid or duplicated SKU.',
						'woocommerce'
					);
					if ( visibleTab !== 'inventory' ) {
						response.errorProps = getErrorPropsWithActions(
							'inventory',
							validatorId,
							focusByValidatiorId
						);
					}
					break;
				case 'product_invalid_global_unique_id':
					response.message = __(
						'Invalid or duplicated GTIN, UPC, EAN or ISBN.',
						'woocommerce'
					);
					if ( visibleTab !== 'inventory' ) {
						response.errorProps = getErrorPropsWithActions(
							'inventory',
							validatorId,
							focusByValidatiorId
						);
					}
					break;
				case 'product_create_error':
					response.message = __(
						'Failed to create product.',
						'woocommerce'
					);
					break;
				case 'product_publish_error':
					response.message = __(
						'Failed to publish product.',
						'woocommerce'
					);
					break;
				case 'product_preview_error':
					response.message = __(
						'Failed to preview product.',
						'woocommerce'
					);
					break;
				default:
					response.message = __(
						'Failed to save product.',
						'woocommerce'
					);
					break;
			}
			return response;
		},
		[]
	);

	return { getProductErrorMessageAndProps };
};
