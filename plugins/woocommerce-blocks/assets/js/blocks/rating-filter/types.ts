export interface Attributes {
	className?: string;
	heading: string;
	headingLevel: number;
	showCounts: boolean;
	showFilterButton: boolean;
	isPreview?: boolean;
}

export interface DisplayOption {
	label: JSX.Element;
	value: string;
}
