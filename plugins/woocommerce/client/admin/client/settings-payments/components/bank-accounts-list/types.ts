export interface BankAccount {
	account_name: string;
	account_number: string;
	bank_name: string;
	sort_code: string;
	iban: string;
	bic: string;
	country_code?: string;
}
