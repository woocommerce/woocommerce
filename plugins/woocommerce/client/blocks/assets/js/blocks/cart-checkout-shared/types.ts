/**
 * External dependencies
 */
import type { Style } from '@wordpress/style-engine/src/types';
import type { CSSProperties } from 'react';

export type ButtonStyleAttributes = {
	style?:
		| ( Style & {
				typography?: Style[ 'typography' ] &
					Pick< CSSProperties, 'textAlign' >;
		  } )
		| string;
	backgroundColor?: string;
	textColor?: string;
	gradient?: string;
	borderColor?: string;
	fontSize?: string;
	fontFamily?: string;
};

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
