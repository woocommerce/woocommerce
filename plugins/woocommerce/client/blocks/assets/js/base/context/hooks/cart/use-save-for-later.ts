/**
 * External dependencies
 */
import { useCallback, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { dispatch, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import { cartStore } from '@woocommerce/block-data';

/**
 * Save a cart line item to the saved-for-later shopper list.
 *
 * Dispatches the wp.data cart store's `saveForLater` thunk, which POSTs the
 * item and emits a `wc-blocks_store_sync_required` event. A Saved for Later
 * block on the same page picks up that event in its iAPI store and applies
 * the new row reactively.
 *
 * Resolves to `true` on success so the caller can chain the cart removal;
 * on failure surfaces an error notice in the cart context and resolves to
 * `false`. The cart removal is the caller's responsibility — keep the two
 * awaits separate so save and remove errors stay distinct.
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
