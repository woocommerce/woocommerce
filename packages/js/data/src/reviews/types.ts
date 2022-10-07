export interface ReviewObject {
	// Type definition below generated from REST API schema.
	/** Unique identifier for the resource. */
	id: number;
	/** The date the review was created, in the site's timezone. date-time string */
	date_created: string;
	/** The date the review was created, as GMT. date-time string */
	date_created_gmt: string;
	/** Unique identifier for the product that the review belongs to. */
	product_id: number;
	/** Product name. */
	product_name: string;
	/** Product URL. */
	product_permalink: string;
	/** Status of the review. */
	status: 'approved' | 'hold' | 'spam' | 'unspam' | 'trash' | 'untrash';
	/** Reviewer name. */
	reviewer: string;
	/** reviewer_email */
	reviewer_email: string;
	/** The content of the review. */
	review: string;
	/** integer 0 to 5 */
	rating: number;
	/** Shows if the reviewer bought the product or not. */
	verified: boolean;
	/** Avatar URLs for the object reviewer. */
	reviewer_avatar_urls: {
		/** Avatar URL with image size of 24 pixels. */
		'24': string;
		/** Avatar URL with image size of 48 pixels. */
		'48': string;
		/** Avatar URL with image size of 96 pixels. */
		'96': string;
	};
	// _embedded type definition from test case https://github.com/woocommerce/woocommerce/blob/63aa382ffb82b0374a4cd1cf5fabf09e6b71043f/plugins/woocommerce-admin/client/homescreen/activity-panel/reviews/test/index.js#L23
	_embedded?: {
		up?: Array< {
			id: number;
			name?: string;
			slug?: string;
			permalink?: string;
			description?: string;
			short_description?: string;
			images?: Array< {
				id: number;
				date_created?: string;
				date_created_gmt?: string;
				date_modified?: string;
				date_modified_gmt?: string;
				src?: string;
				name?: string;
				alt?: string;
			} >;
		} >;
	};
	/** Application state flag to indicate if it is in process of being persisted to backend */
	isUpdating?: boolean;
}

export interface ReviewObjectUpdate
	extends Partial< Omit< ReviewObject, 'id' > >,
		Pick< ReviewObject, 'id' > {}

export interface ReviewsState {
	reviews: {
		[ query: string ]: {
			data: number[];
			totalCount: number;
		};
	};
	errors: {
		[ query: string ]: unknown;
	};
	data: {
		[ reviewId: number ]: Partial< ReviewObject >;
	};
}

export interface ReviewsQueryParams {
	/** Scope under which the request is made; determines fields present in response. */
	context?: 'view' | 'edit';
	/** Current page of the collection. */
	page?: number;
	/** Maximum number of items to be returned in result set. */
	per_page?: number;
	/** Limit results to those matching a string. */
	search?: string;
	/** Limit response to resources published after a given ISO8601 compliant date. */
	after?: string;
	/** Limit response to reviews published before a given ISO8601 compliant date. */
	before?: string;
	/** Ensure result set excludes specific IDs. */
	exclude?: Array< number >;
	/** Limit result set to specific IDs. */
	include?: Array< number >;
	/** Offset the result set by a specific number of items. */
	offset?: number;
	/** Order sort attribute ascending or descending. */
	order?: 'asc' | 'dsc';
	/** Sort collection by object attribute. */
	orderby?: 'date' | 'date_gmt' | 'id' | 'include' | 'product';
	/** Limit result set to reviews assigned to specific user IDs. */
	reviewer?: Array< number >;
	/** Ensure result set excludes reviews assigned to specific user IDs. */
	reviewer_exclude?: Array< number >;
	/** Limit result set to that from a specific author email. */
	reviewer_email?: string;
	/** Limit result set to reviews assigned to specific product IDs. */
	product?: Array< number >;
	/** Limit result set to reviews assigned a specific status. */
	status?: 'all' | 'hold' | 'approved' | 'spam' | 'trash';

	_embed?: number;
}
