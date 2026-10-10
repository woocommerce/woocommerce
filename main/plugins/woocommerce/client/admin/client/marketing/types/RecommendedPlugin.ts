type Subcategory = {
	slug: string;
	name: string;
};

type Tag = {
	slug: string;
	name: string;
};

export type RecommendedPlugin = {
	title: string;
	description: string;
	url: string;
	direct_install: boolean;
	icon: string;
	product: string;
	plugin: string;
	categories: Array< string >;
	subcategories: Array< Subcategory >;
	tags: Array< Tag >;
};
