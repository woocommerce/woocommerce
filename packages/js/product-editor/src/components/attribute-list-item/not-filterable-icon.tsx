/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export default function NotFilterableIcon( {
	width = 24,
	height = 24,
	...props
}: React.SVGProps< SVGSVGElement > ) {
	return (
		<svg
			{ ...props }
			width={ width }
			height={ height }
			viewBox={ `0 0 ${ width } ${ height }` }
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<g>
				<path
					d="M10 17.5H14V16H10V17.5ZM6 6V7.5H18V6H6ZM8 12.5H16V11H8V12.5Z"
					fill="#949494"
				/>
				<rect
					x="16.7734"
					y="4"
					width="1.22727"
					height="16"
					transform="rotate(30 16.7734 4)"
					fill="#949494"
				/>
				<rect
					x="16"
					y="3"
					width="1.22727"
					height="16"
					transform="rotate(30 16 3)"
					fill="white"
				/>
			</g>
			<defs>
				<clipPath id="clip0_4951_450432">
					<rect width="24" height="24" fill="white" />
				</clipPath>
			</defs>
		</svg>
	);
}
