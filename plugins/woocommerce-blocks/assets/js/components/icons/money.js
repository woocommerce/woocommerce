/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';

export default () => (
	<Icon
		icon={
			<svg
				xmlns="http://www.w3.org/2000/svg"
				width="24"
				height="24"
				viewBox="0 0 24 24"
			>
				<mask id="money-mask" width="20" height="14" x="2" y="5" maskUnits="userSpaceOnUse">
					<path fill="#fff" fillRule="evenodd" d="M2 5v14h20V5H2zm5 12c0-1.657-1.343-3-3-3v-4c1.657 0 3-1.343 3-3h10c0 1.657 1.343 3 3 3v4c-1.657 0-3 1.343-3 3H7zm7-5c0-1.7-.9-3-2-3s-2 1.3-2 3 .9 3 2 3 2-1.3 2-3z" clipRule="evenodd" />
				</mask>
				<g mask="url(#money-mask)">
					<path d="M0 0h24v24H0z" />
				</g>
			</svg>
		}
	/>
);
