/** @format */

export default () => (
	<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
		<mask
			id="flash_on_mask"
			mask-type="alpha"
			maskUnits="userSpaceOnUse"
			x="7"
			y="2"
			width="10"
			height="20"
		>
			<path d="M7 2V13H10V22L17 10H13L16 2H7Z" fill="white" />
		</mask>
		<g mask="url(#flash_on_mask)">
			<rect width="24" height="24" fill="#50575D" />
		</g>
	</svg>
);
