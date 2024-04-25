/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';

export const Ideal = () => (
	/* eslint-disable */
	<svg
		xmlns="http://www.w3.org/2000/svg"
		viewBox="0 0 64 40"
		fill="none"
	>
		<path fill="#fff" d="M0 0h64v40H0z"/>
		<mask
			id="a"
			width={64}
			height={40}
			x={0}
			y={0}
			maskUnits="userSpaceOnUse"
			style={{
				maskType: 'luminance',
			}}
		>
			<path fill="#fff" d="M.025 0H63.72v40H.025V0Z"/>
		</mask>
		<g mask="url(#a)">
			<path
				fill="#000"
				d="M34.398 6c3.874.07 7.146 1.194 9.476 3.272a11.52 11.52 0 0 1 3.038 4.408C47.632 15.502 48 17.63 48 20c0 4.96-1.394 8.662-4.14 11.01-2.26 1.928-5.444 2.93-9.462 2.984V34H16V6h18.398Zm-.348 1.86H17.852v24.28H34.05c8.14 0 12.098-3.97 12.098-12.14 0-4.122-1.18-7.264-3.504-9.336C40.588 8.83 37.616 7.86 34.05 7.86Zm-8.06 11.29v8.78H20.7v-8.78h5.29Zm-2.598-7.384a2.994 2.994 0 1 1 0 5.987 2.994 2.994 0 0 1 0-5.987Z"
			/>
			<path
				fill="#DB4093"
				d="M33.796 28.152h-5.38V11.48h5.38-.22c4.488 0 9.262 1.774 9.262 8.356 0 6.958-4.774 8.312-9.26 8.312h.218v.004Z"
			/>
		</g>
	</svg>
	/* eslint-enable */
);
