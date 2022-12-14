// TODO: The following types are copied from plugins/woocommerce-admin/client/marketing/overview-multichannel/DiscoverTools/types.ts.
// They are may be changed later, depending on the outcome of API development.

type Subcategory = {
	slug: string;
	name: string;
};

type Tag = {
	slug: string;
	name: string;
};

export type RecommendedChannel = {
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
