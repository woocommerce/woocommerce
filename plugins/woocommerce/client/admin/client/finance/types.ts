/**
 * Types mirroring the finance REST API schemas in
 * plugins/woocommerce/src/Internal/Admin/Payments/Finance/FinanceDataSchemas.php.
 */

export type FinanceDataType = 'balance' | 'payouts';

export type FinanceProvider = {
	provider_id: string;
	title: string;
	icon_url: string | null;
	data_types: {
		type: FinanceDataType;
		schema_version: number;
	}[];
};

export type FinanceProvidersResponse = {
	providers: FinanceProvider[];
};

export type FinanceLink = {
	title: string;
	url: string;
};

export type Balance = {
	provider_id: string;
	currency: string;
	amount: string;
	available_amount: string | null;
	payout_link: FinanceLink | null;
};

export type PayoutStatus = 'pending' | 'complete' | 'failed';

export type Payout = {
	provider_id: string;
	id: string;
	currency: string;
	amount: string;
	bank_account: string | null;
	date_initiated: string;
	date_expected: string | null;
	status: PayoutStatus;
	provider_status: string | null;
	provider_link: FinanceLink | null;
};

export type FinancePage< T > = {
	schema_version: number;
	items: T[];
	has_more: boolean;
	next_cursor: string | null;
	prev_cursor: string | null;
};

export type FinanceError = {
	code: string;
	message: string;
};
