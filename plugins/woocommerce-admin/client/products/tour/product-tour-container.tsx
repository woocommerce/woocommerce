/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ProductTour } from './product-tour';
import { ProductTourModal } from './product-tour-modal';

const PRODUCT_TOUR_MODAL_HIDDEN = 'woocommerce_product_tour_modal_hidden';

export const ProductTourContainer: React.FC = () => {
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

	const onClose = () => {
		updateOptions( {
			[ PRODUCT_TOUR_MODAL_HIDDEN ]: 'yes',
		} );
	};

	const onStart = () => {
		updateOptions( {
			[ PRODUCT_TOUR_MODAL_HIDDEN ]: 'yes',
		} );
		setIsTouring( true );
	};

	if ( isTouring ) {
		return <ProductTour onClose={ () => setIsTouring( false ) } />;
	}

	if ( isModalHidden ) {
		return null;
	}

	return <ProductTourModal onClose={ onClose } onStart={ onStart } />;
};
