/** @format */

export default () => (
	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<mask
			id="security_mask"
			mask-type="alpha"
			maskUnits="userSpaceOnUse"
			x="3"
			y="1"
			width="18"
			height="22"
		>
			<path
				fillRule="evenodd"
				clipRule="evenodd"
				d="M3 5L12 1L21 5V11C21 16.55 17.16 21.74 12 23C6.84 21.74 3 16.55 3 11V5ZM19
                    11.99H12V3.19L5 6.3V12H12V20.93C15.72 19.78 18.47 16.11 19 11.99Z"
				fill="white"
			/>
		</mask>
		<g mask="url(#security_mask)">
			<rect width="24" height="24" fill="#50575D" />
		</g>
	</svg>
);
