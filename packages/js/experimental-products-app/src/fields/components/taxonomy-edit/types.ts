export type ItemImage = {
	src: string;
	alt?: string;
};

export type Item = {
	label: string;
	value: string;
	disabled?: boolean;
	image?: ItemImage;
};

export type TaxonomyTermRef = {
	id: number;
};
