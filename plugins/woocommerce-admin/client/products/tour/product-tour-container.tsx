/**
 * External dependencies
 */
import { __experimentalUseProductEditorFeedbackFooter as useProductEditorFeedbackFooter } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { ProductTour } from './product-tour';
import { ProductTourModal } from './product-tour-modal';
import { useProductTour } from './use-product-tour';

export const ProductTourContainer: React.FC = () => {
	const { dismissModal, endTour, isModalHidden, isTouring, startTour } =
		useProductTour();
	const { showCesFooter } = useProductEditorFeedbackFooter();

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
