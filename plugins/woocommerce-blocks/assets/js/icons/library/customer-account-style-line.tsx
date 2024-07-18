/**
 * External dependencies
 */
import { Circle, SVG, Path } from '@wordpress/primitives';

const customerAccountStyleLine = (
	<SVG viewBox="5 5 22 22" fill="none" xmlns="http://www.w3.org/2000/svg">
		<Circle
			cx="16"
			cy="10.5"
			r="3.5"
			stroke="currentColor"
			strokeWidth="2"
		/>
		<Path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M11.5 18.5H20.5C21.8807 18.5 23 19.6193 23 21V25.5H25V21C25 18.5147 22.9853 16.5 20.5 16.5H11.5C9.01472 16.5 7 18.5147 7 21V25.5H9V21C9 19.6193 10.1193 18.5 11.5 18.5Z"
			fill="currentColor"
		/>
	</SVG>
);

export default customerAccountStyleLine;
