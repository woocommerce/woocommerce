/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

/**
 * Internal dependencies
 */
import { useValidations } from '../../../../contexts/validation-context';
import { WPError } from '../../../../utils/get-error-message';

export function usePublish( {
	disabled,
	onClick,
	onPublishSuccess,
	onPublishError,
	...props
}: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' > & {
	onPublishSuccess?( product: Product ): void;
	onPublishError?( error: WPError ): void;
} ): Button.ButtonProps {
	const [ productId ] = useEntityProp< number >(
		'postType',
		'product',
		'id'
	);
	const [ productStatus ] = useEntityProp< ProductStatus >(
		'postType',
		'product',
		'status'
	);

	const { isValidating, validate } = useValidations();

	const { isSaving } = useSelect(
		( select ) => {
			const { isSavingEntityRecord } = select( 'core' );

			return {
				isSaving: isSavingEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const isBusy = isSaving || isValidating;

	const isCreating = productStatus === 'auto-draft';

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( onClick ) {
			onClick( event );
		}

		try {
			await validate();

			// The publish button click not only change the status of the product
			// but also save all the pending changes. So even if the status is
			// publish it's possible to save the product too.
			if ( productStatus !== 'publish' ) {
				await editEntityRecord( 'postType', 'product', productId, {
					status: 'publish',
				} );
			}

			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				'product',
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
				onPublishError( error as WPError );
			}
		}
	}

	return {
		children: isCreating
			? __( 'Add', 'woocommerce' )
			: __( 'Save', 'woocommerce' ),
		...props,
		isBusy,
		variant: 'primary',
		onClick: handleClick,
	};
}
