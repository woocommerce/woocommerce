/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { useValidations } from '../../../../contexts/validation-context';
import { WPError } from '../../../../utils/get-product-error-message';
import { PublishButtonProps } from '../../publish-button';

export function usePublish( {
	productType = 'product',
	productStatus,
	disabled,
	onClick,
	onPublishSuccess,
	onPublishError,
	...props
}: PublishButtonProps & {
	onPublishSuccess?( product: Product ): void;
	onPublishError?( error: WPError ): void;
} ): Button.ButtonProps {
	const { isValidating, validate } = useValidations< Product >();

	const [ productId ] = useEntityProp< number >(
		'postType',
		productType,
		'id'
	);

	const { isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord } = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					productType,
					productId
				),
			};
		},
		[ productId ]
	);

	const isBusy = isSaving || isValidating;

	const isPublished =
		productType === 'product' ? productStatus === 'publish' : true;

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( onClick ) {
			onClick( event );
		}

		try {
			if ( productType === 'product' ) {
				await validate( {
					status: 'publish',
				} );
				// The publish button click not only change the status of the product
				// but also save all the pending changes. So even if the status is
				// publish it's possible to save the product too.
				if ( ! isPublished ) {
					await editEntityRecord(
						'postType',
						productType,
						productId,
						{
							status: 'publish',
						}
					);
				}
			} else {
				await validate();
			}

			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				productType,
				productId,
				{
					throwOnError: true,
				}
			);

			if ( publishedProduct && onPublishSuccess ) {
				onPublishSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onPublishError ) {
				let wpError = error as WPError;
				if ( ! wpError.code ) {
					wpError = {
						code: isPublished
							? 'product_publish_error'
							: 'product_create_error',
					} as WPError;
					if ( ( error as Record< string, string > ).variations ) {
						wpError.code = 'variable_product_no_variation_prices';
						wpError.message = (
							error as Record< string, string >
						 ).variations;
					} else {
						const errorMessage = Object.values(
							error as Record< string, string >
						).find( ( value ) => value !== undefined ) as
							| string
							| undefined;
						if ( errorMessage !== undefined ) {
							wpError.code = 'product_form_field_error';
							wpError.message = errorMessage;
						}
					}
				}
				onPublishError( wpError );
			}
		}
	}

	return {
		children: isPublished
			? __( 'Update', 'woocommerce' )
			: __( 'Add', 'woocommerce' ),
		...props,
		isBusy,
		variant: 'primary',
		onClick: handleClick,
	};
}
