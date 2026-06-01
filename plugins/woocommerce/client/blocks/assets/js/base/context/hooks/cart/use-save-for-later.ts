/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dispatch, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { cartStore } from '@woocommerce/block-data';

/**
 * Save a cart line item to the saved-for-later shopper list. Resolves to `true` on success. On failure,
 * surfaces an error notice in the cart context and resolves to `false`. Removing the line from the cart
 * is the caller's responsibility, so save and remove errors can be reported independently.
 */
export const useSaveForLater = (): {
	isSaving: boolean;
	saveForLater: ( cartItemKey: string ) => Promise< boolean >;
} => {
	const [ isSaving, setIsSaving ] = useState( false );
	const { saveForLater: dispatchSaveForLater } = useDispatch( cartStore );

	const saveForLater = useCallback(
		async ( cartItemKey: string ): Promise< boolean > => {
			if ( ! cartItemKey || isSaving ) {
				return false;
			}
			setIsSaving( true );
			try {
				await dispatchSaveForLater( cartItemKey );
				return true;
			} catch ( error ) {
				const message =
					error &&
					typeof error === 'object' &&
					'message' in error &&
					typeof ( error as { message: unknown } ).message ===
						'string'
						? ( error as { message: string } ).message
						: __(
								'There was a problem saving this item for later.',
								'woocommerce'
						  );
				dispatch( noticesStore ).createNotice( 'error', message, {
					context: 'wc/cart',
					isDismissible: true,
				} );
				return false;
			} finally {
				setIsSaving( false );
			}
		},
		[ isSaving, dispatchSaveForLater ]
	);

	return { isSaving, saveForLater };
};
