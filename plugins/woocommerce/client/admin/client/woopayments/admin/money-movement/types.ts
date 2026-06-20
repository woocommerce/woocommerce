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
	order?: WooPaymentsPaymentOrder;
	payment_method_details?: WooPaymentsPaymentMethodDetails;
	outcome?: WooPaymentsPaymentOutcome;
	dispute?: WooPaymentsDispute;
	balance_transaction?: string | WooPaymentsBalanceTransaction;
	application_fee_amount?: number;
	amount_refunded?: number;
	refunded?: boolean;
	captured?: boolean;
	fee?: number;
	net?: number;
	status?: string;
}

export interface WooPaymentsAuthorization {
	id?: string;
	captured?: boolean;
	charge_id?: string;
	created?: number | string;
	order_id?: number | string;
	risk_level?: number | string;
	amount?: number;
	customer_name?: string;
	customer_email?: string;
	customer_country?: string;
	payment_intent_id?: string;
	currency?: string;
}

export interface WooPaymentsAuthorizationsSummary {
	count?: number;
	total_count?: number;
	total?: number;
	currency?: string;
	all_currencies?: string[];
}

export interface WooPaymentsAuthorizationActionResponse {
	id?: string;
	status?: string;
	payment_intent_id?: string;
	[ key: string ]: unknown;
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
	balance_transactions?: Array< Record< string, unknown > >;
	issuer_evidence?: Record< string, unknown > | null;
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
	balance_transaction?: string | WooPaymentsBalanceTransaction;
	type?: string;
	amount?: number;
	currency?: string;
	created?: number | string;
	date?: number | string;
	billing_details?: WooPaymentsBillingDetails;
	order?: WooPaymentsPaymentOrder;
	payment_method_details?: WooPaymentsPaymentMethodDetails;
	outcome?: WooPaymentsPaymentOutcome;
	dispute?: WooPaymentsDispute;
	application_fee_amount?: number;
	amount_refunded?: number;
	refunded?: boolean;
	captured?: boolean;
	status?: string;
}

export interface WooPaymentsPaymentIntent {
	id?: string;
	charge?: WooPaymentsCharge;
	charges?: {
		data?: WooPaymentsCharge[];
	};
	dispute?: WooPaymentsDispute;
	amount?: number;
	currency?: string;
	created?: number | string;
	order?: WooPaymentsPaymentOrder;
	status?: string;
}

export interface WooPaymentsBalanceTransaction {
	id?: string;
	fee?: number;
	net?: number;
	currency?: string;
	[ key: string ]: unknown;
}

export interface WooPaymentsBillingDetails {
	email?: string;
	name?: string;
	[ key: string ]: unknown;
}

export interface WooPaymentsPaymentOrder {
	id?: number | string;
	number?: number | string;
	[ key: string ]: unknown;
}

export interface WooPaymentsPaymentMethodDetails {
	type?: string;
	card?: {
		brand?: string;
		last4?: string;
		[ key: string ]: unknown;
	};
	[ key: string ]: unknown;
}

export interface WooPaymentsPaymentOutcome {
	risk_level?: string;
	[ key: string ]: unknown;
}

export interface WooPaymentsTimelineEvent {
	type?: string;
	message?: string;
	datetime?: number | string;
	created?: number | string;
	user?: {
		username?: string;
		[ key: string ]: unknown;
	};
	[ key: string ]: unknown;
}

export interface WooPaymentsTimelineResponse {
	data?: WooPaymentsTimelineEvent[];
}

export interface WooPaymentsDisputeMetadata {
	/* eslint-disable @typescript-eslint/naming-convention -- provider metadata keys can include leading underscores. */
	__product_type?: string;
	__evidence_submitted_at?: string | number;
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
	loan_id_is?: string | string[];
	status_is?: string | string[];
	status_is_not?: string | string[];
	store_currency_is?: string | string[];
	type_is?: string | string[];
	deposit_id?: string | string[];
	date_after?: string | string[];
	date_before?: string | string[];
	date_between?: string | string[];
	[ key: string ]: string | string[] | number | boolean | undefined;
}

export interface WooPaymentsAuthorizationQuery
	extends WooPaymentsMoneyMovementQuery {
	order_id_is?: string | string[];
	customer_email_is?: string | string[];
	customer_country_is?: string | string[];
	risk_level_is?: string | string[];
}

export type WooPaymentsMoneyMovementQueryFilterParam =
	| 'loan_id_is'
	| 'deposit_id'
	| 'store_currency_is'
	| 'type_is'
	| 'status_is'
	| 'status_is_not'
	| 'date_after'
	| 'date_before'
	| 'date_between';

export type WooPaymentsMoneyMovementSortDirection = 'asc' | 'desc';

export interface WooPaymentsMoneyMovementRouteLocation {
	pathname?: string;
	search?: string;
}

export type WooPaymentsMoneyMovementDataViewFilterOperator =
	| 'is'
	| 'isNot'
	| 'isAny'
	| 'isNone'
	| 'isAll'
	| 'isNotAll';

export interface WooPaymentsMoneyMovementDataViewFilter {
	field: string;
	operator: WooPaymentsMoneyMovementDataViewFilterOperator;
	value: unknown;
}

export interface WooPaymentsMoneyMovementDataView {
	type: 'table';
	search?: string;
	filters?: WooPaymentsMoneyMovementDataViewFilter[];
	sort?: {
		field: string;
		direction: WooPaymentsMoneyMovementSortDirection;
	};
	page?: number;
	perPage?: number;
	fields?: string[];
	titleField?: string;
	showTitle?: boolean;
	layout?: Record< string, unknown >;
}

export interface WooPaymentsFraudOutcomeQuery {
	status?: 'allow' | 'block' | 'review' | string;
	page?: number;
	pagesize?: number;
	sort?: string;
	direction?: string;
	search?: string;
	search_term?: string;
	additional_status?: string;
	[ key: string ]: string | string[] | number | boolean | undefined;
}
