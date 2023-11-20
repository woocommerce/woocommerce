/**
 * External dependencies
 */
import { SVG, Rect, Line, Path } from '@wordpress/components';
import React from 'react';

const mic = (
	<SVG
		width="24"
		height="24"
		viewBox="0 0 24 24"
		xmlns="http://www.w3.org/2000/SVG"
	>
		<Rect
			x="8.75"
			y="2.75"
			width="6.5"
			height="11.5"
			rx="3.25"
			stroke="currentColor"
			strokeWidth="1.5"
			fill="none"
		/>
		<Line
			x1="12"
			y1="17"
			x2="12"
			y2="21"
			stroke="currentColor"
			strokeWidth="1.5"
			fill="none"
		/>
		<Path
			d="M18 11C18 11.7879 17.8448 12.5681 17.5433 13.2961C17.2417 14.0241 16.7998 14.6855 16.2426 15.2426C15.6855 15.7998 15.0241 16.2418 14.2961 16.5433C13.5681 16.8448 12.7879 17 12 17C11.2121 17 10.4319 16.8448 9.7039 16.5433C8.97595 16.2417 8.31451 15.7998 7.75736 15.2426C7.20021 14.6855 6.75825 14.0241 6.45672 13.2961C6.15519 12.5681 6 11.7879 6 11"
			stroke="currentColor"
			strokeWidth="1.5"
			fill="none"
		/>
	</SVG>
);

export default mic;
