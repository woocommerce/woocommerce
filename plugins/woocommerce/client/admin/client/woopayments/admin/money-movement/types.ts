export interface WooPaymentsListResponse< T > {
	data?: T[];
	total_count?: number;
}

export interface WooPaymentsTransaction {
	id?: string;
	transaction_id?: string;
	charge_id?: string;
	payment_intent_id?: string;
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
	charge?:
		| string
		| {
				id?: string;
				payment_intent?: string;
				balance_transaction?: string | { id?: string };
		  };
	payment_intent?: string;
	transaction_id?: string;
	amount?: number;
	currency?: string;
	reason?: string;
	status?: string;
	created?: number | string;
	date?: number | string;
	evidence_due_by?: number | string;
	evidence?: WooPaymentsDisputeEvidence;
	evidence_details?: WooPaymentsDisputeEvidenceDetails;
	metadata?: WooPaymentsDisputeMetadata;
	enhanced_eligibility_types?: string[];
	order?: {
		id?: number;
		number?: string | number;
		suggested_product_type?: string;
		customer_name?: string;
		customer_email?: string;
	};
	customer_name?: string;
	customer_email?: string;
}

export interface WooPaymentsCharge {
	id?: string;
	payment_intent?: string;
	balance_transaction?: string | { id?: string };
	type?: string;
	amount?: number;
	currency?: string;
	created?: number | string;
	date?: number | string;
	status?: string;
}

export interface WooPaymentsPaymentIntent {
	id?: string;
	charge?: WooPaymentsCharge;
	charges?: {
		data?: WooPaymentsCharge[];
	};
	amount?: number;
	currency?: string;
	created?: number | string;
	status?: string;
}

export interface WooPaymentsDisputeMetadata {
	/* eslint-disable @typescript-eslint/naming-convention -- provider metadata keys can include leading underscores. */
	__product_type?: string;
	/* eslint-enable @typescript-eslint/naming-convention */
	[ key: string ]: unknown;
}

export interface WooPaymentsDisputeEvidence {
	receipt?: string;
	customer_communication?: string;
	customer_signature?: string;
	refund_policy?: string;
	duplicate_charge_documentation?: string;
	cancellation_policy?: string;
	cancellation_rebuttal?: string;
	access_activity_log?: string;
	service_documentation?: string;
	shipping_documentation?: string;
	uncategorized_file?: string;
	product_description?: string;
	uncategorized_text?: string;
	shipping_carrier?: string;
	shipping_date?: string;
	shipping_tracking_number?: string;
	shipping_address?: string;
	customer_purchase_ip?: string;
	[ key: string ]: unknown;
}

export interface WooPaymentsDisputeEvidenceDetails {
	due_by?: number | string;
	past_due?: boolean;
	has_evidence?: boolean;
	submission_count?: number;
	[ key: string ]: unknown;
}

export interface WooPaymentsDisputeFile {
	id?: string;
	filename?: string;
	file_name?: string;
	name?: string;
	size?: number;
	type?: string;
	mime_type?: string;
	purpose?: string;
	[ key: string ]: unknown;
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
