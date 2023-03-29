/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button, Icon } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import { check } from '@wordpress/icons';
import { createElement, Fragment } from '@wordpress/element';

export function useSaveDraft( {
	productId,
	disabled,
	onSaveSuccess,
	onSaveError,
	...props
}: Omit< Button.ButtonProps, 'variant' | 'onClick' > & {
	productId: number;
	onSaveSuccess?( product: Product ): void;
	onSaveError?( error: Error ): void;
} ): Button.ButtonProps {
	const { productStatus, hasEdits } = useSelect(
		( select ) => {
			const { getEditedEntityRecord, hasEditsForEntityRecord } =
				select( 'core' );

			const product = getEditedEntityRecord< Product | undefined >(
				'postType',
				'product',
				productId
			);

			return {
				productStatus: product?.status,
				hasEdits: hasEditsForEntityRecord< boolean >(
					'postType',
					'product',
					productId
				),
			};
		},
		[ productId ]
	);

	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick() {
		try {
			await editEntityRecord( 'postType', 'product', productId, {
				status: 'draft',
			} );
			const publishedProduct = await saveEditedEntityRecord< Product >(
				'postType',
				'product',
				productId
			);

			if ( typeof onSaveSuccess === 'function' ) {
				onSaveSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( typeof onSaveError === 'function' ) {
				onSaveError( error as Error );
			}
		}
	}

	let children;
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
		'aria-disabled':
			disabled || ( productStatus !== 'publish' && ! hasEdits ),
		variant: 'tertiary',
		onClick: handleClick,
	};
}
