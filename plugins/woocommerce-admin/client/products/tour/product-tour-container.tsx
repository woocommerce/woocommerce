/**
 * External dependencies
 */
import { useDispatch } from '@wordpress/data';
import { OPTIONS_STORE_NAME } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { ProductTour } from './product-tour';
import { ProductTourModal } from './product-tour-modal';
import { useProductMVPCESFooter } from '~/customer-effort-score-tracks/use-product-mvp-ces-footer';
import { useProductTour } from './use-product-tour';

export const ProductTourContainer: React.FC = () => {
	const { dismissModal, endTour, isModalHidden, isTouring, startTour } =
		useProductTour();
	// const { updateOptions } = useDispatch( OPTIONS_STORE_NAME );
	const { showCesFooter } = useProductMVPCESFooter();
	// const showCesFooter = () => {
	// 	updateOptions( {
	// 		[ PRODUCT_MVP_CES_ACTION_OPTION_NAME ]: 'new_product',
	// 	} );
	// }

	if ( isTouring ) {
		return (
			<ProductTour
				onClose={ () => {
					endTour();
					showCesFooter();
				} }
			/>
		);
	}

	if ( isModalHidden ) {
		return null;
	}

	return (
		<ProductTourModal
			onClose={ () => {
				dismissModal();
				showCesFooter();
			} }
			onStart={ startTour }
		/>
	);
};
