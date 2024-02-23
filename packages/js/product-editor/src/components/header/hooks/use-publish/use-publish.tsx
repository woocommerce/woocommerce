/**
 * External dependencies
 */
import { MouseEvent } from 'react';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import type { Product, ProductVariation } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { useValidations } from '../../../../contexts/validation-context';
import type { WPError } from '../../../../utils/get-product-error-message';
import type { PublishButtonProps } from '../../publish-button';

export function usePublish( {
	productType = 'product',
	disabled,
	onClick,
	onPublishSuccess,
	onPublishError,
	...props
}: PublishButtonProps & {
	onPublishSuccess?( product: Product ): void;
	onPublishError?( error: WPError ): void;
} ): Button.ButtonProps & {
	publish(
		productOrVariation?: Partial< Product | ProductVariation >
	): Promise< Product | ProductVariation | undefined >;
	deleteProduct( force?: boolean ): Promise< Product | ProductVariation >;
} {
	const { isValidating, validate } = useValidations< Product >();

	const [ productId ] = useEntityProp< number >(
		'postType',
		productType,
		'id'
	);

	const [ status, , prevStatus ] = useEntityProp< Product[ 'status' ] >(
		'postType',
		productType,
		'status'
	);

	const { isSaving, isDirty } = useSelect(
		( select ) => {
			const {
				// @ts-expect-error There are no types for this.
				isSavingEntityRecord,
				// @ts-expect-error There are no types for this.
				hasEditsForEntityRecord,
			} = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					productType,
					productId
				),
				isDirty: hasEditsForEntityRecord(
					'postType',
					productType,
					productId
				),
			};
		},
		[ productId ]
	);

	const isBusy = isSaving || isValidating;
	const isDisabled = disabled || isBusy || ! isDirty;

	// @ts-expect-error There are no types for this.
	const { editEntityRecord, saveEditedEntityRecord, deleteEntityRecord } =
		useDispatch( 'core' );

	function catchWPError( error: Error ) {
		const wpError: WPError = {
			code:
				status === 'publish' || status === 'future'
					? 'product_publish_error'
					: 'product_create_error',
			message: error.message,
			data: {},
		};

		if ( 'variations' in error ) {
			wpError.code = 'variable_product_no_variation_prices';
			wpError.message = error.variations as string;
		} else {
			const errorMessage = Object.values( error ).find(
				( value ) => value !== undefined
			) as string | undefined;

			if ( errorMessage !== undefined ) {
				wpError.code = 'product_form_field_error';
				wpError.message = errorMessage;
			}
		}

		return wpError;
	}

	async function publish(
		productOrVariation: Partial< Product | ProductVariation > = {}
	) {
		const isPublished = status === 'publish' || status === 'future';

		try {
			// The publish button click not only change the status of the product
			// but also save all the pending changes. So even if the status is
			// publish it's possible to save the product too.
			const data = ! isPublished
				? { status: 'publish', ...productOrVariation }
				: productOrVariation;

			await validate( data as Partial< Product > );

			await editEntityRecord( 'postType', productType, productId, data );

			const publishedProduct = await saveEditedEntityRecord<
				Product | ProductVariation
			>( 'postType', productType, productId, {
				throwOnError: true,
			} );

			if ( publishedProduct && onPublishSuccess ) {
				onPublishSuccess( publishedProduct );
			}

			return publishedProduct as Product | ProductVariation;
		} catch ( error ) {
			if ( onPublishError ) {
				let wpError = error as WPError;
				if ( ! wpError.code ) {
					wpError = catchWPError( error as Error );
				}
				onPublishError( wpError );
			}
		}
	}

	async function deleteProduct( force = false ) {
		try {
			await validate();

			await saveEditedEntityRecord< Product | ProductVariation >(
				'postType',
				productType,
				productId,
				{
					throwOnError: true,
				}
			);

			return deleteEntityRecord( 'postType', productType, productId, {
				force,
				throwOnError: true,
			} );
		} catch ( error ) {
			let wpError = error as WPError;
			if ( ! wpError.code ) {
				wpError = catchWPError( error as Error );
			}
			throw wpError;
		}
	}

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( isDisabled ) {
			event.preventDefault?.();
			return;
		}

		if ( onClick ) {
			onClick( event );
		}

		await publish();
	}

	function getButtonText() {
		if (
			window.wcAdminFeatures[ 'product-pre-publish-modal' ] &&
			status === 'future'
		) {
			return __( 'Schedule', 'woocommerce' );
		}

		if ( prevStatus === 'publish' || prevStatus === 'future' ) {
			return __( 'Update', 'woocommerce' );
		}

		return __( 'Publish', 'woocommerce' );
	}

	return {
		children: getButtonText(),
		...props,
		isBusy,
		'aria-disabled': isDisabled,
		variant: 'primary',
		onClick: handleClick,
		publish,
		deleteProduct,
	};
}
