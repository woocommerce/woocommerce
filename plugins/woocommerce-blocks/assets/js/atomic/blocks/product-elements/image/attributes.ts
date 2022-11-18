interface BlockAttributes {
	productId: {
		type: string;
		default: number;
	};
	showProductLink: {
		type: string;
		default: boolean;
	};
	showSaleBadge: {
		type: string;
		default: boolean;
	};
	saleBadgeAlign: {
		type: string;
		default: string;
	};
	imageSizing: {
		type: string;
		default: string;
	};
	isDescendentOfQueryLoop: {
		type: string;
		default: boolean;
	};
}
export const blockAttributes: BlockAttributes = {
	showProductLink: {
		type: 'boolean',
		default: true,
	},
	showSaleBadge: {
		type: 'boolean',
		default: true,
	},
	saleBadgeAlign: {
		type: 'string',
		default: 'right',
	},
	imageSizing: {
		type: 'string',
		default: 'full-size',
	},
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
