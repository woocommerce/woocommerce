export interface Attributes {
	className?: string;
	heading: string;
	headingLevel: number;
	showCounts: boolean;
	showFilterButton: boolean;
	isPreview?: boolean;
}

export interface DisplayOption {
	value: string;
	name: string;
	label: JSX.Element;
}
