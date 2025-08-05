/**
 * External dependencies
 */
import { getConfig, getContext, store } from '@wordpress/interactivity';
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
