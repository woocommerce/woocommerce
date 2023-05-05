/**
 * External dependencies
 */
import { Product, ProductStatus } from '@woocommerce/data';
import { Button, Icon } from '@wordpress/components';
import { useEntityProp } from '@wordpress/core-data';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';
import { createElement, Fragment } from '@wordpress/element';
import { MouseEvent, ReactNode } from 'react';

export function useSaveDraft( {
	disabled,
	onClick,
	onSaveSuccess,
	onSaveError,
	...props
}: Omit< Button.ButtonProps, 'aria-disabled' | 'variant' | 'children' > & {
	onSaveSuccess?( product: Product ): void;
	onSaveError?( error: Error ): void;
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

	const { hasEdits, isDisabled } = useSelect(
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
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const ariaDisabled =
		disabled || isDisabled || ( productStatus !== 'publish' && ! hasEdits );

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick( event: MouseEvent< HTMLButtonElement > ) {
		if ( ariaDisabled ) {
			return event.preventDefault();
		}

		if ( onClick ) {
			onClick( event );
		}

		try {
			await editEntityRecord( 'postType', 'product', productId, {
				status: 'draft',
			} );
			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				'product',
				productId
			);

			if ( onSaveSuccess ) {
				onSaveSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( onSaveError ) {
				onSaveError( error as Error );
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
