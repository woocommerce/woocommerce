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
};

export default blockAttributes;
