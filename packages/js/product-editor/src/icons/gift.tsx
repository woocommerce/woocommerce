/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { SVG, Path, Rect } from '@wordpress/components';

const gift = (
	<SVG viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Rect
			x="-0.75"
			y="-0.75"
			fill="none"
			width="9.5"
			height="14.5"
			transform="matrix(3.97376e-08 -1 -1 -4.80825e-08 18.5 18.5)"
			stroke="#1E1E1E"
			strokeWidth="1.5"
		/>
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M13 19L13 9L11.5 9L11.5 19L13 19Z"
		/>
		<Path
			d="M16.5 6.5C16.5 7.4665 15.7165 8.25 14.75 8.25H13V6.5C13 5.5335 13.7835 4.75 14.75 4.75C15.7165 4.75 16.5 5.5335 16.5 6.5Z"
			stroke="#1E1E1E"
			fill="none"
			strokeWidth="1.5"
		/>
		<Path
			d="M8 6.5C8 7.4665 8.7835 8.25 9.75 8.25H11.5V6.5C11.5 5.5335 10.7165 4.75 9.75 4.75C8.7835 4.75 8 5.5335 8 6.5Z"
			stroke="#1E1E1E"
			fill="none"
			strokeWidth="1.5"
		/>
	</SVG>
);

export default gift;
