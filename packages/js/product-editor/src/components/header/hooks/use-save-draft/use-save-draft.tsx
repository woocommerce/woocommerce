/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button, Icon } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';
import { createElement, Fragment } from '@wordpress/element';
import { MouseEvent, ReactNode } from 'react';

/**
 * Internal dependencies
 */
import { useValidations } from '../../../../contexts/validation-context';
import { WPError } from '../../../../utils/get-product-error-message';
import { SaveDraftButtonProps } from '../../save-draft-button';

export function useSaveDraft( {
	productId,
	productStatus,
	disabled,
	onClick,
	onSaveSuccess,
	onSaveError,
	...props
}: SaveDraftButtonProps & {
	onSaveSuccess?( product: Product ): void;
	onSaveError?( error: WPError ): void;
} ): Button.ButtonProps {
	const { hasEdits, isDisabled } = useSelect(
		( select ) => {
			const { hasEditsForEntityRecord, isSavingEntityRecord } =
				select( 'core' );
			const isSaving = isSavingEntityRecord< boolean >(
				'postType',
				'product',
				productId
			);

			return {
				isDisabled: isSaving,
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const { isValidating, validate } = useValidations();

	const ariaDisabled =
		disabled ||
		isDisabled ||
		( productStatus !== 'publish' && ! hasEdits ) ||
		isValidating;

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( ariaDisabled ) {
			return event.preventDefault();
		}

		if ( onClick ) {
			onClick( event );
		}

		try {
			await validate();

			await editEntityRecord( 'postType', 'product', productId, {
				status: 'draft',
			} );
			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				'product',
				productId,
				{
					throwOnError: true,
				}
			);

			if ( onSaveSuccess ) {
				onSaveSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onSaveError ) {
				onSaveError( error as WPError );
			}
		}
	}

	let children: ReactNode;
	if ( productStatus === 'publish' ) {
		children = __( 'Switch to draft', 'woocommerce' );
	} else if ( hasEdits ) {
		children = __( 'Save draft', 'woocommerce' );
	} else {
		children = (
			<>
				<Icon icon={ check } />
				{ __( 'Saved', 'woocommerce' ) }
			</>
		);
	}

	return {
		children,
		...props,
		'aria-disabled': ariaDisabled,
		variant: 'tertiary',
		onClick: handleClick,
	};
}
