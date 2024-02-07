export type ApiFetchError = {
	code: string;
	data: {
		status: number;
	};
	message: string;
};

export type RegisteredChannel = {
	slug: string;
	is_setup_completed: boolean;
	settings_url: string;
	name: string;
	description: string;
	product_listings_status: string;
	errors_count: number;
	icon: string;
};

export type RegisteredChannelsState = {
	data?: Array< RegisteredChannel >;
	error?: ApiFetchError;
};

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

export type RecommendedChannelsState = {
	data?: Array< RecommendedChannel >;
	error?: ApiFetchError;
};

export type Campaign = {
	id: string;
	channel: string;
	title: string;
	manage_url: string;
	cost: {
		value: string;
		currency: string;
	} | null;
	sales: {
		value: string;
		currency: string;
	} | null;
};

export type CampaignsPage = {
	data?: Array< Campaign >;
	error?: ApiFetchError;
};

export type CampaignsMeta = {
	total?: number;
};

export type CampaignsState = {
	pages: Record< string, CampaignsPage >;
	meta: CampaignsMeta;
};

export type CampaignsPagination = {
	campaignsPage: CampaignsPage | null;
	meta: CampaignsMeta;
};

export type CampaignType = {
	id: string;
	name: string;
	description: string;
	channel: {
		slug: string;
		name: string;
	};
	create_url: string;
	icon_url: string;
};

export type CampaignTypesState = {
	data?: Array< CampaignType >;
	error?: ApiFetchError;
};

export type State = {
	registeredChannels: RegisteredChannelsState;
	recommendedChannels: RecommendedChannelsState;
	campaigns: CampaignsState;
	campaignTypes: CampaignTypesState;
};
