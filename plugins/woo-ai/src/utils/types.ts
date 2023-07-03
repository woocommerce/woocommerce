export type Attribute = {
	name: string;
	value: string;
};

export type ProductData = {
	product_id: number | null;
	name: string;
	description: string;
	categories: string[];
	tags: string[];
	attributes: Attribute[];
	product_type: string;
	is_downloadable: boolean;
	is_virtual: boolean;
	publishing_status: string;
};

export type ProductDataSuggestion = {
	reason: string;
	content: string;
};

export type ProductDataSuggestionRequest = {
	requested_data: string;
	name: string;
	description: string;
	categories: string[];
	tags: string[];
	attributes: Attribute[];
};

// This is the standard API response data when an error is returned.
export type ApiErrorResponse = {
	code: string;
	message: string;
	data?: ApiErrorResponseData | undefined;
};

// API errors contain data with the status, and more in-depth error details. This may be null.
export type ApiErrorResponseData = {
	status: number;
} | null;
