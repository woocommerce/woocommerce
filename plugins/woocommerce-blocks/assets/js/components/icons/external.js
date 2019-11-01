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
				<mask id="external-mask" width="24" height="24" x="0" y="0" maskUnits="userSpaceOnUse">
					<path fill="#fff" d="M6.3431 6.3431v1.994l7.8984.0072-8.6055 8.6054 1.4142 1.4143 8.6055-8.6055.0071 7.8984h1.994V6.3431H6.3431z" />
				</mask>
				<g mask="url(#external-mask)">
					<path d="M0 0h24v24H0z" />
				</g>
			</svg>
		}
	/>
);

