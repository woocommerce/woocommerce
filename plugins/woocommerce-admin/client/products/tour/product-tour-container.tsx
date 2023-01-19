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
	const { showCesFooter } = useProductMVPCESFooter();

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
