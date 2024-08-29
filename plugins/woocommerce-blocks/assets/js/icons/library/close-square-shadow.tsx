/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const closeSquareShadow = (
	<SVG
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<Path
			d="M19.05 16.25L8.45 16.25C8.0634 16.25 7.75 15.9366 7.75 15.55L7.75 4.95C7.75 4.5634 8.0634 4.25 8.45 4.25L19.05 4.25C19.4366 4.25 19.75 4.5634 19.75 4.95L19.75 15.55C19.75 15.9366 19.4366 16.25 19.05 16.25Z"
			stroke="currentColor"
			strokeWidth="1.5"
			fill="none"
		/>
		<Path
			d="M15.25 19.75L5.25 19.75C4.69772 19.75 4.25 19.3023 4.25 18.75L4.25 8.75"
			stroke="currentColor"
			strokeWidth="1.5"
			fill="none"
		/>
		<line
			y1="-0.75"
			x2="7"
			y2="-0.75"
			transform="matrix(-0.707107 -0.707107 -0.707107 0.707107 15.6946 13.1497)"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<line
			y1="-0.75"
			x2="7"
			y2="-0.75"
			transform="matrix(-0.707107 0.707107 0.707107 0.707107 16.7551 8.19995)"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</SVG>
);

export default closeSquareShadow;
