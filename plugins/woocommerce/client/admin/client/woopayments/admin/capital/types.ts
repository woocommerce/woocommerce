export interface WooPaymentsCapitalSummary {
	details?: {
		advance_amount: number;
		advance_paid_out_at: number;
		currency: string;
		current_repayment_interval: {
			due_at: number;
			paid_amount: number;
			remaining_amount: number;
		};
		fee_amount: number;
		paid_amount: number;
		remaining_amount: number;
		repayments_begin_at: number;
		withhold_rate: number;
	};
}

export interface WooPaymentsCapitalLoan {
	stripe_loan_id: string;
	amount: number;
	currency: string;
	fee_amount: number;
	withhold_rate: number;
	paid_out_at: string;
	first_paydown_at: string | null;
	fully_paid_at: string | null;
}

export interface WooPaymentsCapitalLoansResponse {
	data?: WooPaymentsCapitalLoan[];
}
