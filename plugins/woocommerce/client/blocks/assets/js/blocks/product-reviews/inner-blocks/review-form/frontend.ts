/**
 * External dependencies
 */
import {
	getConfig,
	getContext,
	getElement,
	store,
} from '@wordpress/interactivity';
import type { HTMLElementEvent } from '@woocommerce/types';

type ServerState = {
	state: {
		hoveredStar: number;
		selectedStar: number;
		ratingError: string;
		hasError: boolean;
	};
};

type StarContext = {
	starValue: string;
};

const productReviewsFormStore = {
	state: {
		get isStarHovered(): boolean {
			const { starValue } = getContext< StarContext >();
			return state.hoveredStar >= parseInt( starValue, 10 );
		},
		get isStarSelected(): boolean {
			const { starValue } = getContext< StarContext >();
			return state.selectedStar >= parseInt( starValue, 10 );
		},
		get hasError(): boolean {
			return state.ratingError.length > 0;
		},
	},
	actions: {
		hoverStar() {
			const { starValue } = getContext< StarContext >();
			state.hoveredStar = parseInt( starValue, 10 );
		},
		leaveStar() {
			state.hoveredStar = 0;
		},
		selectStar() {
			const { starValue } = getContext< StarContext >();
			state.selectedStar = parseInt( starValue, 10 );
		},
		changeRatingWithKeyboard( event: KeyboardEvent ) {
			const { ref } = getElement();
			if ( ! ref || ! ref.parentNode ) {
				return;
			}

			const { starValue } = getContext< StarContext >();
			const starInt = parseInt( starValue, 10 );
			let newRating = starInt;

			let shouldPreventDefault = false;

			if ( event.key === 'ArrowLeft' && starInt > 1 ) {
				newRating = starInt - 1;
				shouldPreventDefault = true;
			} else if ( event.key === 'ArrowRight' && starInt < 5 ) {
				newRating = starInt + 1;
				shouldPreventDefault = true;
			} else if ( event.key === 'Home' ) {
				newRating = 1;
				shouldPreventDefault = true;
			} else if ( event.key === 'End' ) {
				newRating = 5;
				shouldPreventDefault = true;
			} else if ( event.key === ' ' || event.key === 'Enter' ) {
				// Activate current star.
				event.preventDefault();
				state.selectedStar = starInt;
				return;
			}

			if ( shouldPreventDefault ) {
				event.preventDefault();
				state.selectedStar = newRating;

				// Focus management - only move focus for navigation keys.
				const nextButton =
					ref.parentNode.querySelector< HTMLButtonElement >(
						`button:nth-child(${ newRating })`
					);
				if ( nextButton ) {
					nextButton.focus();
				}
			}
		},
		handleSubmit( event: HTMLElementEvent< HTMLFormElement > ) {
			const formData = new FormData( event.target );
			const rating = formData.get( 'rating' ) as string | null;
			const config = getConfig( 'woocommerce/product-reviews' );
			if (
				config.review_rating_required &&
				( ! rating || parseInt( rating, 10 ) === 0 )
			) {
				event.preventDefault();
				state.ratingError = config.i18n_required_rating_text;
				return;
			}

			state.ratingError = '';
		},
	},
};

const { state } = store< ServerState & typeof productReviewsFormStore >(
	'woocommerce/product-reviews',
	productReviewsFormStore,
	{
		lock: 'I acknowledge that using a private store means my plugin will inevitably break on the next store release.',
	}
);
