/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const productFilterRating = () => (
	<SVG
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<line
			x1="4"
			y1="15.25"
			x2="20"
			y2="15.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<line
			x1="4"
			y1="19.25"
			x2="13"
			y2="19.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<Path
			d="M8.92572 3.95425C9.01742 3.76843 9.28238 3.76844 9.37409 3.95425L10.4877 6.21072C10.5241 6.28451 10.5945 6.33565 10.676 6.34748L13.1661 6.70933C13.3712 6.73912 13.4531 6.99112 13.3047 7.13575L11.5028 8.89217C11.4439 8.94961 11.417 9.03236 11.4309 9.11346L11.8563 11.5936C11.8913 11.7978 11.6769 11.9535 11.4935 11.8571L9.26624 10.6862C9.19341 10.6479 9.1064 10.6479 9.03357 10.6862L6.80629 11.8571C6.62288 11.9535 6.40853 11.7978 6.44355 11.5936L6.86893 9.11346C6.88283 9.03236 6.85595 8.94961 6.79703 8.89217L4.99512 7.13575C4.84674 6.99112 4.92862 6.73912 5.13367 6.70933L7.62385 6.34748C7.70527 6.33565 7.77566 6.28451 7.81208 6.21072L8.92572 3.95425Z"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
			fill="none"
		/>
	</SVG>
);

export default productFilterRating;
