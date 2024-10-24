/**
 * Internal dependencies
 */
import {
	defaultPlaceOrderButtonLabel,
	defaultReturnToCartButtonLabel,
} from './constants';

export default {
	cartPageId: {
		type: 'number',
		default: 0,
	},
	showReturnToCart: {
		type: 'boolean',
		default: false,
	},
	className: {
		type: 'string',
		default: '',
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
	placeOrderButtonLabel: {
		type: 'string',
		default: defaultPlaceOrderButtonLabel,
	},
	returnToCartButtonLabel: {
		type: 'string',
		default: defaultReturnToCartButtonLabel,
	},
};
