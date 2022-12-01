type BlockAttributes = {
	productId: {
		type: string;
		default: number;
	};
};

export const blockAttributes: BlockAttributes = {
	productId: {
		type: 'number',
		default: 0,
	},
	isDescendentOfQueryLoop: {
		type: 'boolean',
		default: false,
	},
};

export default blockAttributes;
