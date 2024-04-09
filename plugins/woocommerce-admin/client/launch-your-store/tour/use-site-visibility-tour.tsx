/**
 * External dependencies
 */
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, dispatch } from '@wordpress/data';
import { useState } from 'react';

const LYS_TOUR_HIDDEN = 'woocommerce_launch_your_store_tour_hidden';

export const useSiteVisibilityTour = () => {
	const [ showTour, setShowTour ] = useState( true );
	const { shouldTourBeShown } = useSelect( ( select ) => {
		// Tour should only be shown if the user has not seen it before and the `woocommerce_show_lys_tour` option is "yes" (for sites upgrading from a previous WooCommerce version)

		const { getCurrentUser } = select( 'core' );
		const wasTourShown =
			( getCurrentUser() as { meta?: { [ key: string ]: string } } )
				?.meta?.[ LYS_TOUR_HIDDEN ] === 'yes';

		const { getOption } = select( OPTIONS_STORE_NAME );

		const showLYSTourOption = getOption( 'woocommerce_show_lys_tour' );

		const _shouldTourBeShown =
			showLYSTourOption === 'yes' && ! wasTourShown;

		return {
			shouldTourBeShown: _shouldTourBeShown,
		};
	} );

	const onClose = () => {
		dispatch( 'core' ).saveUser( {
			id: window?.wcSettings?.currentUserId,
			meta: {
				woocommerce_launch_your_store_tour_hidden: 'yes',
			},
		} );
	};

	return {
		onClose,
		shouldTourBeShown,
		showTour,
		setShowTour,
	};
};
