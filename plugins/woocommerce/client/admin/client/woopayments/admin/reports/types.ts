export type ReportsTab = 'balance' | 'fees';

export type ReportsSortDirection = 'asc' | 'desc';

export type ReportsBalanceQuery = {
	date_start?: string;
	date_end?: string;
	currency?: string;
};

export type ReportsFeesQuery = {
	page?: number | string;
	per_page?: number | string;
	sort?: string;
	direction?: ReportsSortDirection;
	date_before?: string;
	date_after?: string;
	date_between?: string[];
	payment_method_type?: string;
	type?: string | string[];
	search?: string | string[];
	user_timezone?: string;
	user_email?: string;
	locale?: string;
};

export type ReportsFeesViewFilter = {
	field: string;
	operator: string;
	value: unknown;
};

export type ReportsFeesView = {
	type?: string;
	search?: unknown;
	filters?: ReportsFeesViewFilter[];
	sort?: {
		field?: string;
		direction?: unknown;
	};
	page?: unknown;
	perPage?: unknown;
};

export type ReportsBalanceSummaryRow = {
	amount?: number;
	count?: number;
};

export type ReportsBalanceSummary = {
	currency?: string;
	period?: {
		start?: string;
		end?: string;
	};
	starting_balance?: ReportsBalanceSummaryRow;
	total_charges_captured?: ReportsBalanceSummaryRow;
	fees?: ReportsBalanceSummaryRow;
	charge_fees?: ReportsBalanceSummaryRow;
	payout_fees?: ReportsBalanceSummaryRow;
	reader_fees?: ReportsBalanceSummaryRow;
	dispute_fees?: ReportsBalanceSummaryRow;
	fee_refunds?: ReportsBalanceSummaryRow;
	refunds?: ReportsBalanceSummaryRow;
	refund_failure?: ReportsBalanceSummaryRow;
	disputes?: ReportsBalanceSummaryRow;
	financing_payout?: ReportsBalanceSummaryRow;
	financing_paydown?: ReportsBalanceSummaryRow;
	network_costs?: ReportsBalanceSummaryRow;
	other_adjustments?: ReportsBalanceSummaryRow;
	net_balance_change_in_the_period?: ReportsBalanceSummaryRow;
	payouts?: ReportsBalanceSummaryRow;
	ending_balance?: ReportsBalanceSummaryRow;
};

export type ReportsFee = {
	transaction_id: string;
	date?: string;
	payment_id?: string;
	payment_method?: {
		type?: string;
	};
	type?: string;
	transaction_currency?: string;
	amount?: number;
	deposit_currency?: string;
	fees?: number;
	order_id?: number | string | null;
	deposit_date?: string | null;
	deposit_id?: string | null;
};

export type ReportsFeesSummary = {
	count?: number;
	total?: number;
	fees?: number;
	net?: number;
	currency?: string;
	sources?: string[];
	types?: string[];
};
