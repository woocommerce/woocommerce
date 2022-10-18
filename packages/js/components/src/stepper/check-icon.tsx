/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export default () => {
	// we need a unique mask id because HTML ids are global in nature and collisions result in strange outcomes
	const maskId = `check-icon-mask-${ Math.floor(
		Math.random() * 10000000
	) }`;
	return (
		<svg
			role="img"
			aria-hidden="true"
			focusable="false"
			width="18"
			height="18"
			viewBox="0 0 18 18"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<mask
				id={ maskId }
				mask-type="alpha"
				maskUnits="userSpaceOnUse"
				x="2"
				y="3"
				width="14"
				height="12"
			>
				<path
					d="M6.59631 11.9062L3.46881 8.77875L2.40381 9.83625L6.59631 14.0287L15.5963
                5.02875L14.5388 3.97125L6.59631 11.9062Z"
					fill="white"
				/>
			</mask>
			<g mask={ `url(#${ maskId })` }>
				<rect width="18" height="18" fill="white" />
			</g>
		</svg>
	);
};
