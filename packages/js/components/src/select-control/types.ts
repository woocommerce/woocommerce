export type Option = {
	key: string;
	label: string | React.ReactNode;
	isDisabled?: boolean;
	keywords?: Array< string >;
	value?: unknown;
};

type SelectedOption = Omit< Option, 'label' > & {
	label: string;
};

export type Selected = string | SelectedOption[];
