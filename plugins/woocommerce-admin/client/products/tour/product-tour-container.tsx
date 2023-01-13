/**
 * Internal dependencies
 */
import { ProductTour } from './product-tour';
import { ProductTourModal } from './product-tour-modal';
import { useProductTour } from './use-product-tour';

export const ProductTourContainer: React.FC = () => {
	const { dismissModal, endTour, isModalHidden, isTouring, startTour } =
		useProductTour();

	if ( isTouring ) {
		return <ProductTour onClose={ endTour } />;
	}

	if ( isModalHidden ) {
		return null;
	}

	return <ProductTourModal onClose={ dismissModal } onStart={ startTour } />;
};
