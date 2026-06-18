export type WooPaymentsDepositStatus =
	| 'paid'
	| 'pending'
	| 'in_transit'
	| 'canceled'
	| 'failed';

export interface WooPaymentsMoneyAmount {
	amount: number;
	currency: string;
	source_types?: Record< string, number >;
}

export interface WooPaymentsInstantBalance {
	amount: number;
	currency: string;
	fee: number;
	net: number;
	fee_percentage: number;
}

export interface WooPaymentsDepositSchedule {
	delay_days?: number;
	interval?: 'manual' | 'daily' | 'weekly' | 'monthly';
	weekly_anchor?: string;
	monthly_anchor?: number;
}

export interface WooPaymentsExternalAccount {
	currency: string;
	status: string;
}

export interface WooPaymentsDepositsAccount {
	default_currency: string;
	account_link?: string | false;
	deposits_enabled?: boolean;
	deposits_blocked?: boolean;
	deposits_disabled?: boolean;
	deposits_schedule?: WooPaymentsDepositSchedule;
	completed_waiting_period?: boolean;
	minimum_scheduled_deposit_amounts?: Record< string, number >;
	default_external_accounts?: WooPaymentsExternalAccount[];
}

export interface WooPaymentsDeposit {
	id: string;
	date: number | string;
	type: string;
	amount: number;
	status: WooPaymentsDepositStatus | string;
	bankAccount?: string | null;
	bank_reference_key?: string | null;
	currency: string | null;
	automatic?: boolean;
	fee?: number;
	fee_percentage?: number;
	created?: number;
	failure_code?: string;
	failure_message?: string;
}

export interface WooPaymentsDepositsOverview {
	deposit?: {
		last_paid?: WooPaymentsDeposit[];
	};
	balance?: {
		pending?: WooPaymentsMoneyAmount[];
		available?: WooPaymentsMoneyAmount[];
		instant?: WooPaymentsInstantBalance[];
	};
	account: WooPaymentsDepositsAccount;
}

export interface WooPaymentsDepositsListResponse {
	data: WooPaymentsDeposit[];
	total_count: number;
}

export interface WooPaymentsDepositsSummary {
	store_currencies?: string[];
	count?: number;
	total?: number;
	currency?: string;
}

export interface WooPaymentsDepositsQuery {
	page?: number | string;
	pagesize?: number | string;
	sort?: string;
	direction?: 'asc' | 'desc' | string;
	match?: string;
	store_currency_is?: string;
	date_before?: string;
	date_after?: string;
	date_between?: string;
	status_is?: string;
	status_is_not?: string;
}
