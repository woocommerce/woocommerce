/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { MouseEvent } from 'react';

export function usePublish( {
	disabled,
	onClick,
	onPublishSuccess,
	onPublishError,
	...props
}: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' > & {
	onPublishSuccess?( product: Product ): void;
	onPublishError?( error: Error ): void;
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

	const { hasEdits, isDisabled, isBusy } = useSelect(
		( select ) => {
			const { hasEditsForEntityRecord, isSavingEntityRecord } =
				select( 'core' );
			const { isPostSavingLocked } = select( 'core/editor' );
			const isSavingLocked = isPostSavingLocked();
			const isSaving = isSavingEntityRecord< boolean >(
				'postType',
				'product',
				productId
			);

			return {
				isDisabled: isSavingLocked || isSaving,
				isBusy: isSaving,
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const isCreating = productStatus === 'auto-draft';
	const ariaDisabled =
		disabled || isDisabled || ( productStatus === 'publish' && ! hasEdits );

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( ariaDisabled ) {
			return event.preventDefault();
		}

		if ( onClick ) {
			onClick( event );
		}

		try {
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
				productId
			);

			if ( onPublishSuccess ) {
				onPublishSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onPublishError ) {
				onPublishError( error as Error );
			}
		}
	}

	return {
		children: isCreating
			? __( 'Add', 'woocommerce' )
			: __( 'Save', 'woocommerce' ),
		...props,
		'aria-disabled': ariaDisabled,
		isBusy,
		variant: 'primary',
		onClick: handleClick,
	};
}
