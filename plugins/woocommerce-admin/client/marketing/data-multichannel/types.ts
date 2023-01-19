export type ApiFetchError = {
	code: string;
	data: {
		status: number;
	};
	message: string;
};

export type Channel = {
	slug: string;
	is_setup_completed: boolean;
	settings_url: string;
	name: string;
	description: string;
	product_listings_status: string;
	errors_count: number;
	icon: string;
};

export type Channels = {
	data?: Array< Channel >;
	error?: ApiFetchError;
};

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

export type RecommendedChannels = {
	data?: Array< RecommendedPlugin >;
	error?: ApiFetchError;
};

export type State = {
	channels: Channels;
	recommendedChannels: RecommendedChannels;
};
