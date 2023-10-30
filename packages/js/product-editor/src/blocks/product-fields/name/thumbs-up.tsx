/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export function ThumbsUpSVG( { rotate = false } ) {
	const props = rotate ? { transform: 'rotate(180)' } : {};
	return (
		<svg
			{ ...props }
			width="18"
			height="18"
			viewBox="0 0 18 18"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<g clipPath="url(#clip0_1782_275585)">
				<path
					d="M11.448 1.80014C11.583 1.78214 11.682 1.81814 11.817 1.86314C12.321 2.03414 12.564 2.57414 12.411 3.07814C12.258 3.57314 11.511 5.81414 11.511 6.30014C11.511 6.77714 12.186 7.20014 12.726 7.20014H15.426C15.966 7.20014 16.326 7.56014 16.326 8.10014C16.326 8.64014 14.526 14.4001 14.526 14.4001C14.373 14.7511 14.031 15.3001 13.626 15.3001H5.4V7.20014H7.326C7.695 6.83114 10.296 2.96114 10.548 2.45714C10.737 2.08814 11.088 1.84514 11.448 1.80014ZM1.8 7.20014H3.6V15.3001H1.8V7.20014Z"
					fill="#949494"
				/>
			</g>
			<defs>
				<clipPath id="clip0_1782_275585">
					<rect width="18" height="18" fill="white" />
				</clipPath>
			</defs>
		</svg>
	);
}
