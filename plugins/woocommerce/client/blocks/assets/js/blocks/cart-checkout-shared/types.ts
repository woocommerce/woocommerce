export type ExpressCheckoutAttributes = {
	className?: string;
	buttonHeight: string;
	showButtonStyles: boolean;
	buttonBorderRadius: string;
	lock: {
		move: boolean;
		remove: boolean;
	};
};

export type ExpressCartAttributes = {
	className: string;
	buttonHeight: string;
	showButtonStyles: boolean;
	buttonBorderRadius: string;
};

export type ExpressPaymentSettings = {
	showButtonStyles: boolean;
	buttonHeight: string;
	buttonBorderRadius: string;
};
