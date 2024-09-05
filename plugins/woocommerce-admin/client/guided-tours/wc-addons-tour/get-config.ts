/**
 * External dependencies
 */
import { TourKitTypes } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import { scrollPopperToVisibleAreaIfNeeded } from './utils';

export const getTourConfig = ( {
	closeHandler,
	onNextStepHandler,
	autoScrollBlock,
	steps,
}: {
	closeHandler: TourKitTypes.CloseHandler;
	onNextStepHandler: ( currentStepIndex: number ) => void;
	autoScrollBlock: ScrollLogicalPosition;
	steps: TourKitTypes.WooStep[];
} ): TourKitTypes.WooConfig => {
	let previousPopperTopPosition: number | null = null;
	let perviousPopperRef: unknown = null;
	const defaultPlacement = 'top-start';

	return {
		placement: defaultPlacement,
		options: {
			effects: {
				spotlight: {
					interactivity: {
						enabled: true,
						rootElementSelector: '.woocommerce-marketplace',
					},
				},
				autoScroll: {
					behavior: 'auto',
					block: autoScrollBlock,
				},
			},
			popperModifiers: [
				{
					name: 'offset',
					options: {
						offset: [ 20, 20 ],
					},
				},
				{
					name: 'flip',
					options: {
						allowedAutoPlacements: [ 'right', 'bottom', 'top' ],
						fallbackPlacements: [ 'bottom-start', 'right' ],
						flipVariations: false,
						boundry: 'clippingParents',
					},
				},
				{
					name: 'inAppTourPopperModifications',
					enabled: true,
					phase: 'read',
					fn( { state, instance } ) {
						// 1. First modification - force `right` placement for items in admin menu.
						if ( perviousPopperRef !== state.elements.reference ) {
							const isAdminMenuItem = (
								state.elements.reference as HTMLElement
							 ).closest( '#adminmenu' );
							const desiredPlacement = isAdminMenuItem
								? 'right'
								: defaultPlacement;
							if ( state.placement !== desiredPlacement ) {
								instance.setOptions( {
									placement: desiredPlacement,
								} );
							}
						}

						// 2. Second modification - Try to make sure that the popper is visible once when
						// the next step is displayed.
						const popperBoundingRect =
							state.elements.popper.getBoundingClientRect();
						const arrowBoundingRect =
							state.elements.arrow?.getBoundingClientRect();
						const arrowHeight = arrowBoundingRect?.height || 0;

						// Try to make sure that the popper is visible if poppers' reference (step) changed and
						// if arrowHeight is not 0 (it means that popper's position hasn't been updated yet).
						// Also, change if popper's top position changed - the modifier can be called
						// multiple times for the same position.
						if (
							perviousPopperRef !== state.elements.reference &&
							arrowHeight !== 0 &&
							previousPopperTopPosition !== popperBoundingRect.top
						) {
							scrollPopperToVisibleAreaIfNeeded(
								popperBoundingRect
							);
							previousPopperTopPosition = popperBoundingRect.top;
							perviousPopperRef = state.elements.reference;
						}
					},
				},
			],
			callbacks: {
				onNextStep: onNextStepHandler,
			},
		},
		steps,
		closeHandler,
	};
};
