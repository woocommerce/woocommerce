export interface ReactSettingsField {
	id: string;
	label: string;
	type: string;
	options?: ReactSettingsFieldOptions;
	desc?: string;
}

export type ReactSettingsOptionItem = {
	label: string;
	value: string;
	desc?: string;
	description?: string;
};

export type ReactSettingsFieldOptions =
	| Record< string, string >
	| ReactSettingsOptionItem[];

export interface ReactSettingsGroup {
	title: string;
	description: string;
	order: number;
	fields: ReactSettingsField[];
}

export interface ReactSettingsResponse {
	id: string;
	title: string;
	description: string;
	values: Record< string, unknown >;
	groups: Record< string, ReactSettingsGroup >;
}

export type RowConfiguration = {
	id: string;
	fields: string[];
};

export type RowConfigurations = Record< string, RowConfiguration[] >;

export type FieldTransformer = (
	field: ReactSettingsField
) => Record< string, unknown >;
