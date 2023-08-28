/**
 * External dependencies
 */
import { SVG } from '@wordpress/primitives';

export const Icon = () => (
	<svg
		width="18"
		height="18"
		viewBox="0 0 18 18"
		fill="none"
		xmlns="http://www.w3.org/2000/svg"
	>
		<path
			fillRule="evenodd"
			clipRule="evenodd"
			d="M6.22448 1.5L1.5 6.81504V11.7072L5.12953 9.06066C5.38061 8.87758 5.71858 8.86829 5.97934 9.0373L8.90601 10.9342L12.4772 7.46225C12.7683 7.17925 13.2317 7.17925 13.5228 7.46225L16.5 10.3568V2C16.5 1.72386 16.2761 1.5 16 1.5H6.22448ZM1.5 13.5636V16C1.5 16.2761 1.72386 16.5 2 16.5H16C16.2761 16.5 16.5 16.2761 16.5 16V12.4032L16.4772 12.4266L13 9.04603L9.52279 12.4266C9.27191 12.6706 8.88569 12.7086 8.59206 12.5183L5.59643 10.5766L1.5 13.5636ZM0 2C0 0.89543 0.895431 0 2 0H16C17.1046 0 18 0.895431 18 2V16C18 17.1046 17.1046 18 16 18H2C0.89543 18 0 17.1046 0 16V2Z"
			fill="#1E1E1E"
		/>
	</svg>
);

export const NextButton = ( { suffixClass }: { suffixClass: string } ) => (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="49"
		height="48"
		viewBox="0 0 49 48"
		fill="none"
		className={ `wc-block-product-gallery-large-image-next-previous-right--${ suffixClass }` }
	>
		<g filter="url(#filter0_b_397_11354)">
			<rect
				x="0.5"
				width="48"
				height="48"
				rx="5"
				fill="black"
				fillOpacity="0.5"
			/>
			<path
				d="M21.7001 12L19.3 14L28.5 24L19.3 34L21.7001 36L32.5 24L21.7001 12Z"
				fill="white"
			/>
		</g>
		<defs>
			<filter
				id="filter0_b_397_11354"
				x="-9.5"
				y="-10"
				width="68"
				height="68"
				filterUnits="userSpaceOnUse"
				colorInterpolationFilters="sRGB"
			>
				<feFlood floodOpacity="0" result="BackgroundImageFix" />
				<feGaussianBlur in="BackgroundImageFix" stdDeviation="5" />
				<feComposite
					in2="SourceAlpha"
					operator="in"
					result="effect1_backgroundBlur_397_11354"
				/>
				<feBlend
					mode="normal"
					in="SourceGraphic"
					in2="effect1_backgroundBlur_397_11354"
					result="shape"
				/>
			</filter>
		</defs>
	</SVG>
);

export const PrevButton = ( { suffixClass }: { suffixClass: string } ) => (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="49"
		height="48"
		viewBox="0 0 49 48"
		fill="none"
		className={ `wc-block-product-gallery-large-image-next-previous-left--${ suffixClass }` }
	>
		<g filter="url(#filter0_b_397_11356)">
			<rect
				x="0.5"
				width="48"
				height="48"
				rx="5"
				fill="black"
				fillOpacity="0.5"
			/>
			<path
				d="M28.1 12L30.5 14L21.3 24L30.5 34L28.1 36L17.3 24L28.1 12Z"
				fill="white"
			/>
		</g>
		<defs>
			<filter
				id="filter0_b_397_11356"
				x="-9.5"
				y="-10"
				width="68"
				height="68"
				filterUnits="userSpaceOnUse"
				colorInterpolationFilters="sRGB"
			>
				<feFlood floodOpacity="0" result="BackgroundImageFix" />
				<feGaussianBlur in="BackgroundImageFix" stdDeviation="5" />
				<feComposite
					in2="SourceAlpha"
					operator="in"
					result="effect1_backgroundBlur_397_11356"
				/>
				<feBlend
					mode="normal"
					in="SourceGraphic"
					in2="effect1_backgroundBlur_397_11356"
					result="shape"
				/>
			</filter>
		</defs>
	</SVG>
);

export const InsideTheImage = () => (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="30"
		height="18"
		viewBox="0 0 30 18"
		fill="none"
	>
		<path
			d="M4.525 8.8L6.825 6.5L5.825 5.5L3.525 7.8C2.825 8.5 2.825 9.6 3.525 10.3L5.825 12.6L6.925 11.5L4.625 9.2C4.425 9.1 4.425 8.9 4.525 8.8Z"
			fill="currentColor"
		/>
		<path
			d="M25.4 8.8L23.1 6.5L24.1 5.5L26.4 7.8C27.1 8.5 27.1 9.6 26.4 10.3L24.1 12.6L23 11.5L25.3 9.2C25.5 9.1 25.5 8.9 25.4 8.8Z"
			fill="currentColor"
		/>
		<rect
			x="0.75"
			y="0.75"
			width="28.5"
			height="16.5"
			rx="1.25"
			stroke="currentColor"
			strokeWidth="1.5"
		/>
	</SVG>
);

export const OutsideTheImage = () => (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="38"
		height="18"
		viewBox="0 0 38 18"
		fill="none"
	>
		<path
			d="M1.525 8.3L5.825 4L4.825 3L0.525 7.3C-0.175 8 -0.175 9.1 0.525 9.8L4.825 14.1L5.925 13L1.625 8.7C1.425 8.6 1.425 8.4 1.525 8.3Z"
			fill="currentColor"
		/>
		<path
			d="M37.325 7.3L33.025 3L31.925 4.1L36.2251 8.4C36.3251 8.5 36.3251 8.7 36.2251 8.8L31.925 13.1L33.025 14.2L37.325 9.9C38.025 9.1 38.025 8 37.325 7.3Z"
			fill="currentColor"
		/>
		<path
			d="M25.925 0H11.925C10.825 0 9.92505 0.9 9.92505 2V16C9.92505 17.1 10.825 18 11.925 18H25.925C27.025 18 27.925 17.1 27.925 16V2C27.925 0.9 27.025 0 25.925 0ZM11.925 1.5H25.925C26.225 1.5 26.425 1.7 26.425 2V10.4L23.425 7.5C23.125 7.2 22.625 7.2 22.425 7.5L18.825 11L15.925 9C15.625 8.8 15.325 8.8 15.125 9L11.525 11.6V2C11.425 1.7 11.625 1.5 11.925 1.5ZM25.925 16.5H11.925C11.625 16.5 11.425 16.3 11.425 16V13.6L15.525 10.6L18.525 12.5C18.825 12.7 19.225 12.7 19.425 12.4L22.925 9L26.425 12.4V16C26.425 16.3 26.225 16.5 25.925 16.5Z"
			fill="currentColor"
		/>
	</SVG>
);
