export type Settings = Record< string, unknown >;

export type SettingsGroup = {
	[ key: string ]: Settings;
};

export type SettingsState = {
	[ key: string ]: {
		data: unknown;
		error: unknown;
		lastReceived: Date;
		isRequesting?: boolean;
		dirty?: string[];
	};
};
