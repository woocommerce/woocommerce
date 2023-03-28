/**
 * External dependencies
 */
import { Product } from '@woocommerce/data';
import { Button } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';

export function usePublish( {
	productId,
	disabled,
	onPublishSuccess,
	onPublishError,
	...props
}: Omit< Button.ButtonProps, 'variant' | 'onClick' > & {
	productId: number;
	onPublishSuccess( product: Product ): void;
	onPublishError?( error: Error ): void;
} ): Button.ButtonProps {
	const { editEntityRecord, saveEditedEntityRecord } = useDispatch( 'core' );

	async function handleClick() {
		try {
			await editEntityRecord( 'postType', 'product', productId, {
				status: 'publish',
			} );
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
		'aria-disabled': disabled,
		variant: 'primary',
		onClick: handleClick,
	};
}
