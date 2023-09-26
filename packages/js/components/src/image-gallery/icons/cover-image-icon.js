/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export const CoverImageIcon = () => (
	<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
		<mask id="path-1-inside-1_7913_41727" fill="white">
			<rect x="4" y="3" width="16" height="9" rx="1.11111" />
		</mask>
		<rect
			x="4"
			y="3"
			fill="none"
			width="16"
			height="9"
			rx="1.11111"
			stroke="currentColor"
			strokeWidth="3"
			mask="url(#path-1-inside-1_7913_41727)"
		/>
		<path
			d="M5 9.5L7.28571 7.83333L9 8.94444L11 7L15.25 11.25"
			stroke="currentColor"
			fill="none"
			strokeWidth="1.5"
			strokeLinejoin="round"
		/>
		<line
			x1="4"
			y1="19.25"
			x2="13"
			y2="19.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<line
			x1="4"
			y1="15.25"
			x2="20"
			y2="15.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</svg>
);
