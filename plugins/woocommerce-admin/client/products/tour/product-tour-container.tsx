/**
 * External dependencies
 */
import { __experimentalUseFeedbackBar as useFeedbackBar } from '@woocommerce/product-editor';

/**
 * Internal dependencies
 */
import { ProductTour } from './product-tour';
import { ProductTourModal } from './product-tour-modal';
import { useProductTour } from './use-product-tour';

export const ProductTourContainer: React.FC = () => {
	const { dismissModal, endTour, isModalHidden, isTouring, startTour } =
		useProductTour();
	const { maybeShowFeedbackBar } = useFeedbackBar();

	if ( isTouring ) {
		return (
			<ProductTour
				onClose={ () => {
					endTour();
					maybeShowFeedbackBar();
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
				maybeShowFeedbackBar();
			} }
			onStart={ startTour }
		/>
	);
};
