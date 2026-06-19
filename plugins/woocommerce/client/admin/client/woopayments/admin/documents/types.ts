export interface WooPaymentsDocument {
	id?: string;
	document_id?: string;
	date?: string | number;
	type?: string;
	period_from?: string | number;
	period_to?: string | number;
	description?: string;
	[ key: string ]: unknown;
}

export interface WooPaymentsDocumentsListResponse {
	data?: WooPaymentsDocument[];
	total_count?: number;
}

export interface WooPaymentsDocumentsSummary {
	count?: number;
	total_count?: number;
	[ key: string ]: unknown;
}

export interface WooPaymentsDocumentsQuery {
	page?: number;
	pagesize?: number;
	sort?: string;
	direction?: 'asc' | 'desc' | string;
	match?: string | string[];
	date_before?: string | string[];
	date_after?: string | string[];
	date_between?: string | string[];
	type_is?: string | string[];
	type_is_not?: string | string[];
	[ key: string ]: string | string[] | number | undefined;
}

export interface WooPaymentsDocumentsDataViewFilter {
	field: string;
	operator: string;
	value: unknown;
}

export interface WooPaymentsDocumentsDataView {
	type: 'table';
	page?: number;
	perPage?: number;
	search?: string;
	sort?: {
		field: string;
		direction: 'asc' | 'desc';
	};
	filters?: WooPaymentsDocumentsDataViewFilter[];
	fields?: string[];
	titleField?: string;
	showTitle?: boolean;
	layout?: Record< string, unknown >;
}

export interface WooPaymentsDocumentsAccountResponse {
	account: {
		connected?: boolean;
		live?: boolean;
		test_mode?: boolean;
		test_drive?: boolean;
		sandbox?: boolean;
		mode?: string;
		[ key: string ]: unknown;
	} | null;
	documents?: {
		enabled?: boolean;
		has_submitted_vat_data?: boolean;
		country?: string;
	};
	urls?: Record< string, string | undefined >;
}

export interface WooPaymentsVatValidationResponse {
	valid?: boolean;
	vat_number?: string;
	name?: string;
	address?: string;
	country_code?: string;
	[ key: string ]: unknown;
}

export interface WooPaymentsVatDetails {
	vat_number: string | null;
	name: string;
	address: string;
}
