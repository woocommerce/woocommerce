import {
	customerBilling,
	customerShipping,
	customerBillingSearchTest,
	customerShippingSearchTest,
} from './customer';
import type { CustomerBilling, CustomerShipping } from './customer';
import { batch, getBatchPayloadExample } from './batch-update';
import type { BatchAction, BatchPayload } from './batch-update';
import { errorResponse } from './error-response';
import type { ErrorResponse } from './error-response';

export {
	customerBilling,
	customerShipping,
	customerBillingSearchTest,
	customerShippingSearchTest,
	batch,
	getBatchPayloadExample,
	errorResponse,
};

export type {
	CustomerBilling,
	CustomerShipping,
	BatchAction,
	BatchPayload,
	ErrorResponse,
};
