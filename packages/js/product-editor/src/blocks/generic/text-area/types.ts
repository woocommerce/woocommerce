export type TextAreaBlockEdit = {
	align: 'left' | 'center' | 'right' | 'justify';
	allowedFormats?: string[];
	direction: 'ltr' | 'rtl';
	label: string;
	property: string;
	helpText?: string;
};
