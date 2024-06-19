export type SummaryAttributes = {
	align: 'left' | 'center' | 'right' | 'justify';
	allowedFormats?: string[];
	direction: 'ltr' | 'rtl';
	label: string;
	property: string;
	helpText?: string;
};
