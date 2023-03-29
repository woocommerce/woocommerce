/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';

export function usePublish( {
	productId,
	disabled,
	onPublishSuccess,
	onPublishError,
	...props
}: Omit< Button.ButtonProps, 'variant' | 'onClick' > & {
	productId: number;
	onPublishSuccess?( product: Product ): void;
	onPublishError?( error: Error ): void;
} ): Button.ButtonProps {
	const { productStatus, hasEdits } = useSelect(
		( select ) => {
			const { getEditedEntityRecord, hasEditsForEntityRecord } =
				select( 'core' );

			const product = getEditedEntityRecord< Product >(
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

			if ( typeof onPublishSuccess === 'function' ) {
				onPublishSuccess( publishedProduct );
			}
		} catch ( error ) {
			if ( typeof onPublishError === 'function' ) {
				onPublishError( error as Error );
			}
		}
	}

	return {
		...props,
		'aria-disabled':
			disabled || ( productStatus === 'publish' && ! hasEdits ),
		variant: 'primary',
		onClick: handleClick,
	};
}
