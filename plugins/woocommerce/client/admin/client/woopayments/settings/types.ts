export type WooPaymentsAccountMode = string;

export type WooPaymentsAccount = {
	id: string;
	mode: WooPaymentsAccountMode;
	default_currency: string;
	connected: boolean;
	working: boolean;
	can_process_payments: boolean;
	test_mode: boolean;
	test_drive: boolean;
	sandbox: boolean;
	live: boolean;
};

export type WooPaymentsAccountUrls = {
	overview_page?: string;
	setup?: string;
};

export type WooPaymentsDocuments = {
	enabled: boolean;
	has_submitted_vat_data: boolean;
	country: string;
};

export type WooPaymentsAccountResponse = {
	account: WooPaymentsAccount | null;
	documents?: WooPaymentsDocuments;
	urls: WooPaymentsAccountUrls;
};
