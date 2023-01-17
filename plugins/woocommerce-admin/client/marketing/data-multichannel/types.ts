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

export type State = {
	channels: Channels;
};
