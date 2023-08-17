export type Option = {
	key: string | number;
	label: string | React.ReactNode;
	isDisabled?: boolean;
	keywords?: Array< string >;
	value?: unknown;
};

export type Selected = string | Option[];
