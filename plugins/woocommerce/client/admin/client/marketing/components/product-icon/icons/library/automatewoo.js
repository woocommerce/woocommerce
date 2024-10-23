/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const automatewoo = (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="100"
		height="100"
		viewBox="0 0 100 100"
	>
		<defs>
			<clipPath id="b">
				<rect width="100" height="100" />
			</clipPath>
		</defs>
		<g id="a" clipPath="url(#b)">
			<rect width="100" height="100" fill="#fff" />
			<rect width="100" height="100" fill="#7532e4" />
			<g transform="translate(-43.503 -133.512)">
				<Path
					d="M78.217,193.13H64.405l-2.823,7.764H54.6L67.648,166.9h7.669l12.934,33.995H81.059Zm-11.6-6.047h9.4L71.33,174.245Z"
					transform="translate(0 0)"
					fill="#1ff2e6"
				/>
				<Path
					d="M246.639,166.9h6.753l-9.4,33.995h-6.81l-7.764-24.208-7.764,24.208h-6.906L205.3,166.9h7l6.238,23.388,7.535-23.388h6.849l7.592,23.483Z"
					transform="translate(-121.952)"
					fill="#1ff2e6"
				/>
			</g>
		</g>
	</SVG>
);

export default automatewoo;
