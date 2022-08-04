export type Settings = {
	[ key: string ]: unknown;
} & {
	general?: {
		[ key: string ]: string;
	};
	tax?: {
		[ key: string ]: string;
	};
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
