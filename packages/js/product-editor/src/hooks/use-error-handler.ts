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
	) => Promise< {
		message: string;
		errorProps: ErrorProps;
	} >;
};

function getUrl( tab: string ): string {
	return getNewPath( { tab } );
}

function getErrorPropsWithActions(
	errorContext = '',
	validatorId: string,
	focusByValidatorId: ( validatorId: string ) => void,
	label: string = __( 'View error', 'woocommerce' )
): ErrorProps {
	return {
		explicitDismiss: true,
		actions: [
			{
				label,
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
	const { focusByValidatorId, getFieldByValidatorId } = useValidations();
	const { getClientIdByField, getParentTabId, getParentTabIdByBlockName } =
		useBlocksHelper();

	async function getClientIdByValidatorId( validatorId: string ) {
		if ( ! validatorId ) {
			return null;
		}
		const field = await getFieldByValidatorId( validatorId );
		if ( ! field ) {
			return null;
		}
		return getClientIdByField( field );
	}

	const getProductErrorMessageAndProps = useCallback(
		async ( error: WPError, visibleTab: string | null ) => {
			const response = {
				message: '',
				errorProps: {} as ErrorProps,
			};
			const { code, message: errorMessage, validatorId = '' } = error;

			const clientId = await getClientIdByValidatorId( validatorId );
			const errorContext = getParentTabId( clientId );

			switch ( code ) {
				case 'variable_product_no_variation_prices':
					response.message = errorMessage;
					if (
						visibleTab !== 'variations' &&
						errorContext !== null
					) {
						response.errorProps = getErrorPropsWithActions(
							errorContext,
							validatorId,
							focusByValidatorId
						);
					}
					break;
				case 'product_form_field_error':
					response.message = errorMessage;
					if (
						visibleTab !== errorContext &&
						errorContext !== null
					) {
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
					const errorSkuContext = getParentTabIdByBlockName(
						'woocommerce/product-sku-field'
					);
					if (
						visibleTab !== errorSkuContext &&
						errorSkuContext !== null
					) {
						response.errorProps = getErrorPropsWithActions(
							errorSkuContext,
							'sku',
							focusByValidatorId,
							__( 'View SKU field', 'woocommerce' )
						);
					}
					break;
				case 'product_invalid_global_unique_id':
					response.message = __(
						'Invalid or duplicated GTIN, UPC, EAN or ISBN.',
						'woocommerce'
					);
					const errorUniqueIdContext = errorContext || 'inventory';
					if ( visibleTab !== errorUniqueIdContext ) {
						response.errorProps = getErrorPropsWithActions(
							errorUniqueIdContext,
							'global_unique_id',
							focusByValidatorId,
							__( 'View identifier field', 'woocommerce' )
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
