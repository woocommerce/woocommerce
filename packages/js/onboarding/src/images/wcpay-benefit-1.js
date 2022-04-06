/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export default ( { width = 141, height = 148, ...props } ) => (
	<svg
		width={ width }
		height={ height }
		viewBox={ `-10 0 ${ width } ${ height }` }
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
		{ ...props }
	>
		<rect
			x="16.3334"
			y="16"
			width="76"
			height="76"
			rx="12"
			fill="#DCDCDE"
		/>
		<g filter="url(#filter0_d_10798_5797)">
			<rect
				x="32.3334"
				y="32"
				width="76"
				height="84"
				rx="12"
				fill="white"
			/>
		</g>
		<rect
			x="26.3334"
			y="26"
			width="40"
			height="40"
			rx="20"
			fill="#9A69C7"
		/>
		<mask
			id="mask0_10798_5797"
			style={ { maskType: 'alpha' } }
			maskUnits="userSpaceOnUse"
			x="36"
			y="38"
			width="21"
			height="16"
		>
			<path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M54.3334 38H38.3334C37.2284 38 36.3334 38.895 36.3334 40V52C36.3334 53.105 37.2284 54 38.3334 54H54.3334C55.4384 54 56.3334 53.105 56.3334 52V40C56.3334 38.895 55.4384 38 54.3334 38ZM54.3334 40V42H38.3334V40H54.3334ZM38.3334 46V52H54.3334V46H38.3334ZM40.3334 48H47.3334V50H40.3334V48ZM52.3334 48H49.3334V50H52.3334V48Z"
				fill="white"
			/>
		</mask>
		<g mask="url(#mask0_10798_5797)">
			<rect x="34.3334" y="34" width="24" height="24" fill="white" />
		</g>
		<mask
			id="mask1_10798_5797"
			style={ { maskType: 'alpha' } }
			maskUnits="userSpaceOnUse"
			x="74"
			y="47"
			width="16"
			height="18"
		>
			<path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M88.4572 55.528C88.288 56.2905 87.6114 56.833 86.8305 56.833H78.1672V58.4997H86.5005C87.4214 58.4997 88.1672 59.2455 88.1672 60.1663H78.1672C77.2464 60.1663 76.5005 59.4205 76.5005 58.4997V56.833V50.1663V49.333H74.8339V47.6663H76.5005C77.4214 47.6663 78.1672 48.4122 78.1672 49.333V50.1663H89.8339L88.4572 55.528ZM79.834 62.6663C79.834 63.583 79.084 64.333 78.1673 64.333C77.2507 64.333 76.509 63.583 76.509 62.6663C76.509 61.7497 77.2507 60.9997 78.1673 60.9997C79.084 60.9997 79.834 61.7497 79.834 62.6663ZM86.5002 60.9997C85.5835 60.9997 84.8418 61.7497 84.8418 62.6663C84.8418 63.583 85.5835 64.333 86.5002 64.333C87.4168 64.333 88.1668 63.583 88.1668 62.6663C88.1668 61.7497 87.4168 60.9997 86.5002 60.9997Z"
				fill="white"
			/>
		</mask>
		<g mask="url(#mask1_10798_5797)">
			<rect x="72.3334" y="46" width="20" height="20" fill="#A7AAAD" />
		</g>
		<rect
			x="45.3334"
			y="92"
			width="36.8966"
			height="11.0345"
			rx="5.51724"
			fill="#674399"
		/>
		<rect
			x="46.3344"
			y="73"
			width="48.9989"
			height="4.13793"
			rx="2.06897"
			fill="#DCDCDE"
		/>
		<rect x="46.3334" y="81" width="49" height="4" rx="2" fill="#DCDCDE" />
		<defs>
			<filter
				id="filter0_d_10798_5797"
				x="0.333374"
				y="0"
				width="140"
				height="148"
				filterUnits="userSpaceOnUse"
				colorInterpolationFilters="sRGB"
			>
				<feFlood floodOpacity="0" result="BackgroundImageFix" />
				<feColorMatrix
					in="SourceAlpha"
					type="matrix"
					values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 127 0"
					result="hardAlpha"
				/>
				<feOffset />
				<feGaussianBlur stdDeviation="16" />
				<feComposite in2="hardAlpha" operator="out" />
				<feColorMatrix
					type="matrix"
					values="0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0 0.1 0"
				/>
				<feBlend
					mode="normal"
					in2="BackgroundImageFix"
					result="effect1_dropShadow_10798_5797"
				/>
				<feBlend
					mode="normal"
					in="SourceGraphic"
					in2="effect1_dropShadow_10798_5797"
					result="shape"
				/>
			</filter>
		</defs>
	</svg>
);
