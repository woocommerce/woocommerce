/**
 * External dependencies
 */
import { SVG, Path } from '@wordpress/primitives';

const productFilterStockStatus = () => (
	<SVG
		width="24"
		height="24"
		viewBox="0 0 24 24"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<line
			x1="4"
			y1="15.25"
			x2="20"
			y2="15.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<line
			x1="4"
			y1="19.25"
			x2="13"
			y2="19.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
		<Path
			d="M4.75 4.8999C4.75 4.20955 5.30964 3.6499 6 3.6499H8.34082C8.7889 3.6499 9.20271 3.88974 9.42544 4.27854L9.62184 4.62136C9.84457 5.01016 10.2584 5.25 10.7065 5.25H13C13.6904 5.25 14.25 5.80964 14.25 6.5V10.3999C14.25 11.0903 13.6904 11.6499 13 11.6499H6C5.30964 11.6499 4.75 11.0903 4.75 10.3999V4.8999Z"
			stroke="currentColor"
			strokeWidth="1.5"
			strokeLinejoin="round"
			fill="none"
		/>
	</SVG>
);

export default productFilterStockStatus;
