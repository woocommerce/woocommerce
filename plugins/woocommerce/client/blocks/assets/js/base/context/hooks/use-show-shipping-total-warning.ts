/**
 * External dependencies
 */
import { useDispatch, useSelect } from '@wordpress/data';
import { cartStore, checkoutStore } from '@woocommerce/block-data';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { hasShippingRate } from '@woocommerce/base-utils';
import { store as noticesStore } from '@wordpress/notices';
import type { WPNotice } from '@wordpress/notices/build-types/store/selectors';

/**
 * Internal dependencies
 */
import { useShippingData } from './shipping/use-shipping-data';

export const useShowShippingTotalWarning = () => {
	const context = 'woocommerce/checkout-totals-block';
	const errorNoticeId = 'wc-blocks-totals-shipping-warning';

	const { shippingRates, hasSelectedLocalPickup } = useShippingData();
	const hasRates = hasShippingRate( shippingRates );
	const { prefersCollection, isRateBeingSelected, shippingNotices } =
		useSelect( ( select ) => {
			return {
				prefersCollection: select( checkoutStore ).prefersCollection(),
				isRateBeingSelected:
					select( cartStore ).isShippingRateBeingSelected(),
				shippingNotices: select( noticesStore ).getNotices( context ),
			};
		}, [] );
	const { createInfoNotice, removeNotice } = useDispatch( noticesStore );

	useEffect( () => {
		const isShowingNotice =
			shippingNotices.length > 0 &&
			shippingNotices.some(
				( notice: WPNotice ) => notice.id === errorNoticeId
			);
		const hasMismatch = ! prefersCollection && hasSelectedLocalPickup;

		if ( ! hasRates || isRateBeingSelected ) {
			// Early return because shipping rates were not yet loaded from the cart data store, or the user is changing
			// rate, no need to alter the notice until we know what the actual rate is.
			if ( isShowingNotice ) {
				// Removes the notice in case it was already shown.
				removeNotice( errorNoticeId, context );
			}
			return;
		}

		// There is a mismatch between the method the user chose (pickup or shipping) and the currently selected rate.
		if ( hasMismatch && ! isShowingNotice ) {
			createInfoNotice(
				__(
					'Totals will be recalculated when a valid shipping method is selected.',
					'woocommerce'
				),
				{
					id: 'wc-blocks-totals-shipping-warning',
					isDismissible: false,
					context,
				}
			);
			return;
		}

		// Don't show the notice if they have selected local pickup, or if they have selected a valid regular shipping rate.
		if ( ! hasMismatch && isShowingNotice ) {
			removeNotice( errorNoticeId, context );
		}
	}, [
		hasSelectedLocalPickup,
		createInfoNotice,
		hasRates,
		isRateBeingSelected,
		prefersCollection,
		removeNotice,
		shippingNotices,
		shippingRates,
	] );
};
