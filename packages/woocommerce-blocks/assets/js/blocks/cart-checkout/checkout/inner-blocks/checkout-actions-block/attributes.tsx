export default {
	cartPageId: {
		type: 'number',
		default: 0,
	},
	showReturnToCart: {
		type: 'boolean',
		default: true,
	},
	lock: {
		type: 'object',
		default: {
			move: true,
			remove: true,
		},
	},
};
