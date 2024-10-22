/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME, useUserPreferences } from '@woocommerce/data';
import { useSelect } from '@wordpress/data';
import { useState } from 'react';

export const useSiteVisibilityTour = () => {
	const [ showTour, setShowTour ] = useState( true );

	// Tour should only be shown if the user has not seen it before and the `woocommerce_show_lys_tour` option is "yes" (for sites upgrading from a previous WooCommerce version)
	const shouldStoreShowLYSTour = useSelect(
		( select ) =>
			select( OPTIONS_STORE_NAME ).getOption(
				'woocommerce_show_lys_tour'
			) === 'yes'
	);

	/**
	 * This is temporary to support sites upgrading from a previous version of WooCommerce.
	 * We used user meta to store the tour dismissal state but now we use WooCommerce meta instead.
	 * It will be removed in WC 9.4.
	 */
	const hasUserDismissedTourMeta = useSelect( ( select ) => {
		const currentUser = select( 'core' ).getCurrentUser();
		if ( ! currentUser ) {
			// If the user is not logged in, we don't want to show the tour.
			return true;
		}

		return (
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			( currentUser as { meta: { [ key: string ]: string } } ).meta
				.woocommerce_launch_your_store_tour_hidden === 'yes'
		);
	} );

	const {
		launch_your_store_tour_hidden: lysTourHidden,
		updateUserPreferences,
	} = useUserPreferences();

	const onClose = () => {
		updateUserPreferences( {
			launch_your_store_tour_hidden: 'yes',
		} );
	};

	return {
		onClose,
		shouldTourBeShown:
			shouldStoreShowLYSTour &&
			! ( hasUserDismissedTourMeta || lysTourHidden === 'yes' ),
		showTour,
		setShowTour,
	};
};
