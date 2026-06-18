export interface WooPaymentsListResponse< T > {
	data?: T[];
	total_count?: number;
}

export interface WooPaymentsTransaction {
	id?: string;
	transaction_id?: string;
	charge_id?: string;
	type?: string;
	amount?: number;
	currency?: string;
	created?: number | string;
	date?: number | string;
	customer_name?: string;
	customer_email?: string;
	status?: string;
}

export interface WooPaymentsDispute {
	id?: string;
	dispute_id?: string;
	charge_id?: string;
	charge?: string | { id?: string };
	amount?: number;
	currency?: string;
	reason?: string;
	status?: string;
	created?: number | string;
	date?: number | string;
	order?: {
		id?: number;
		number?: string | number;
	};
}

export interface WooPaymentsMoneyMovementQuery {
	page?: number;
	pagesize?: number;
	sort?: string;
	direction?: string;
	search?: string | string[];
	status_is?: string;
	status_is_not?: string;
	store_currency_is?: string;
	deposit_id?: string;
	[ key: string ]: string | string[] | number | boolean | undefined;
}
