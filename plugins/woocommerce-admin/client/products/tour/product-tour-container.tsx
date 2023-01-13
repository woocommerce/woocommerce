/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';
import { useSelect, useDispatch } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ProductTourModal } from './product-tour-modal';

const PRODUCT_TOUR_MODAL_HIDDEN = 'woocommerce_product_tour_modal_hidden';

export const ProductTourContainer: React.FC = () => {
	const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { isHidden } = useSelect( ( select ) => {
		const { getOption, hasFinishedResolution } =
			select( OPTIONS_STORE_NAME );

		return {
			isHidden:
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

	const onStart = () => {};

	if ( isHidden ) {
		return null;
	}

	return <ProductTourModal onClose={ onClose } onStart={ onStart } />;
};
