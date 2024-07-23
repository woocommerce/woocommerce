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
import { useBlocksHelper } from './use-blocks-helper';

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
	focusByValidatorId: ( validatorId: string ) => void
): ErrorProps {
	const errorLinkLabel =
		errorContext === 'inventory'
			? __( 'View SKU field', 'woocommerce' )
			: __( 'View error', 'woocommerce' );
	return {
		explicitDismiss: true,
		actions: [
			{
				label: errorLinkLabel,
				onClick: async () => {
					await focusByValidatorId( validatorId );
					navigateTo( {
						url: getUrl( errorContext ),
					} );
				},
			},
		],
	};
}

export const useErrorHandler = (): UseErrorHandlerTypes => {
	const { focusByValidatorId } = useValidations();
	const { getParentTabId } = useBlocksHelper();

	const getProductErrorMessageAndProps = useCallback(
		( error: WPError, visibleTab: string | null ) => {
			const response = {
				message: '',
				errorProps: {} as ErrorProps,
			};
			const {
				code,
				context = '',
				message: errorMessage,
				validatorId = '',
			} = error;
			const errorContext = getParentTabId( context );
			switch ( code ) {
				case 'variable_product_no_variation_prices':
					response.message = errorMessage;
					if ( visibleTab !== 'variations' ) {
						response.errorProps = getErrorPropsWithActions(
							errorContext,
							validatorId,
							focusByValidatorId
						);
					}
					break;
				case 'product_form_field_error':
					response.message = errorMessage;
					if ( visibleTab !== errorContext ) {
						response.errorProps = getErrorPropsWithActions(
							errorContext,
							validatorId,
							focusByValidatorId
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
							'product_sku',
							focusByValidatorId
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
							focusByValidatorId
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
