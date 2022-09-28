export interface Attributes {
	className?: string;
	heading: string;
	headingLevel: number;
	isPreview?: boolean;
}

export interface DisplayOption {
	label: JSX.Element;
	value: string;
}
