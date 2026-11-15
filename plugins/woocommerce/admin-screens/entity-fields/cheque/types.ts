export interface ChequeSettings {
	id: string;
	enabled: boolean;
	title: string;
	description: string;
	settings: {
		// The endpoint returns `{ value }` but takes the plain value, so edited records hold a string.
		instructions?: string | { value: string };
	};
}
