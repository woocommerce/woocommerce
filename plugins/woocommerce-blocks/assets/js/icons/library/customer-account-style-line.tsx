/**
 * External dependencies
 */
import { Circle, SVG, Path } from '@wordpress/primitives';

const customerAccountStyleLine = (
	<SVG
		width="12"
		height="13"
		viewBox="0 0 12 13"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<Circle
			cx="6"
			cy="3"
			r="2.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M3 8.5H9C9.82843 8.5 10.5 9.17157 10.5 10V13H12V10C12 8.34315 10.6569 7 9 7H3C1.34315 7 0 8.34314 0 10V13H1.5V10C1.5 9.17157 2.17157 8.5 3 8.5Z"
			fill="currentColor"
		/>
	</SVG>
);

export default customerAccountStyleLine;
