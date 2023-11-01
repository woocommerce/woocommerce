import { ProductVariation } from '@woocommerce/data';

export type GetVariationsPageRequest = {
	product_id: number;
	page?: number;
	per_page?: number;
	order?: 'asc' | 'desc';
	orderby?: string;
	attributes?: [];
};

export type GetVariationsPageResponse = {
	items: ProductVariation[];
	totalCount: number;
	totalPages: number;
};

export type BatchUpdateRequest = {
	product_id: number;
	update: ( Pick< ProductVariation, 'id' > &
		Omit< Partial< ProductVariation >, 'id' > )[];
};

export type BatchUpdateResponse = {
	update: ProductVariation[];
};

export type Actions = {
	GET_PAGE: {
		request: GetVariationsPageRequest;
		response: GetVariationsPageResponse;
	};
	GET_ALL: {
		request: number;
		response: Record< number, ProductVariation >;
	};
	BATCH_UPDATE: {
		request: BatchUpdateRequest;
		response: BatchUpdateResponse;
	};
	IS_UPDATING: {
		request: undefined;
		response: Record< number, boolean >;
	};
};

export type IncomeMessage< T extends keyof Actions > = {
	action: T;
	payload: Actions[ T ][ 'request' ];
};

export type OutcomeMessage< T extends keyof Actions > = {
	action: T;
	payload: Actions[ T ][ 'response' ];
};
