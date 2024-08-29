/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export const PaymentCardIcon = () => (
	<svg
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<mask
			id="mask0_5908_1374"
			style={ { maskType: 'luminance' } }
			maskUnits="userSpaceOnUse"
			x="2"
			y="4"
			width="20"
			height="16"
		>
			<path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M20 4H4C2.895 4 2 4.895 2 6V18C2 19.105 2.895 20 4 20H20C21.105 20 22 19.105 22 18V6C22 4.895 21.105 4 20 4ZM20 6V8H4V6H20ZM4 12V18H20V12H4ZM6 14H13V16H6V14ZM18 14H15V16H18V14Z"
				fill="white"
			/>
		</mask>
		<g mask="url(#mask0_5908_1374)">
			<rect width="24" height="24" fill="#674399" />
		</g>
	</svg>
);
