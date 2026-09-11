export type SettingsEntityValue = string | number | boolean | unknown[];

export interface SettingsEntityRecord {
	id: string;
	title?: string;
	description?: string;
	values?: Record< string, SettingsEntityValue >;
	groups?: Record<
		string,
		{
			title?: string;
			description?: string;
		}
	>;
}
