/**
 * Internal dependencies
 */
import type { OfflineGatewaySettings } from '../offline/types';

export interface BankAccount {
	account_name: string;
	account_number: string;
	bank_name: string;
	sort_code: string;
	iban: string;
	bic: string;
	country_code?: string;
}

/**
 * The record of the internal bank accounts route.
 */
export interface BacsAccountsRecord {
	accounts?: BankAccount[];
	default_country?: string;
}

/**
 * The gateway record, with the accounts record under its entity name.
 */
export interface BacsSettings extends OfflineGatewaySettings {
	bacs_accounts?: BacsAccountsRecord;
}
