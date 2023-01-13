/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

const PRODUCT_TOUR_MODAL_HIDDEN = 'woocommerce_product_tour_modal_hidden';

export const useProductTour = () => {
	const [ isTouring, setIsTouring ] = useState( false );
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { isModalHidden } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			isModalHidden:
				getOption( PRODUCT_TOUR_MODAL_HIDDEN ) === 'yes' ||
				! hasFinishedResolution( 'getOption', [
					PRODUCT_TOUR_MODAL_HIDDEN,
				] ),
		};
	} );

	const dismissModal = () => {
		updateOptions( {
			[ PRODUCT_TOUR_MODAL_HIDDEN ]: 'yes',
		} );
	};

	const endTour = () => {
		setIsTouring( false );
	};

	const startTour = () => {
		dismissModal();
		setIsTouring( true );
	};

	return {
		dismissModal,
		endTour,
		isModalHidden,
		isTouring,
		startTour,
	};
};
