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
