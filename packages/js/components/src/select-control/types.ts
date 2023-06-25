export type Option = {
	key: string;
	label: string;
	isDisabled?: boolean;
	keywords?: Array< string >;
	value?: unknown;
};

export type Selected = string | Option[];
