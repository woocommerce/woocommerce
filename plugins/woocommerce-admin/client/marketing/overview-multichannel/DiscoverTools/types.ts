type Subcategory = {
	slug: string;
	name: string;
};

type Tag = {
	slug: string;
	name: string;
};

export type Plugin = {
	title: string;
	description: string;
	url: string;
	icon: string;
	product: string;
	plugin: string;
	categories: Array< string >;
	subcategories: Array< Subcategory >;
	tags: Array< Tag >;
};
