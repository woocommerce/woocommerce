/**
 * External dependencies
 */
import { IconProps } from '@wordpress/icons/build-types/icon';
import { Path, SVG } from '@wordpress/primitives';

const CheckMark = ( props: IconProps ) => (
	<SVG
		viewBox="0 0 10 8"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		{ ...props }
	>
		<Path
			d="M9.25 1.19922L3.75 6.69922L1 3.94922"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinecap="round"
			strokeLinejoin="round"
		/>
	</SVG>
);

export default CheckMark;
