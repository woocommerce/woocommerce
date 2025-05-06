/**
 * External dependencies
 */
import { getElement, store } from '@wordpress/interactivity';

function isValidLink( ref: HTMLElement | null ): ref is HTMLAnchorElement {
	return (
		ref !== null &&
		ref instanceof window.HTMLAnchorElement &&
		!! ref.href &&
		( ! ref.target || ref.target === '_self' ) &&
		ref.origin === window.location.origin
	);
}

const productReviewsStore = {
	actions: {
		*navigate( event: MouseEvent ) {
			event.preventDefault();
			const { ref } = getElement();

			if ( ! isValidLink( ref ) ) {
				return;
			}

			console.log( ref, ref.href );

			const { actions } = yield import(
				'@wordpress/interactivity-router'
			);

			yield actions.navigate( ref.href );
		},
	},
};

store< typeof productReviewsStore >(
	'woocommerce/blockified-product-reviews',
	productReviewsStore,
	{
		lock: true,
	}
);
