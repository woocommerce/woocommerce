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

export const NextButton = () => (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="49"
		height="48"
		viewBox="0 0 49 48"
		fill="none"
		className={ `wc-block-product-gallery-large-image-next-previous-right` }
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

export const PrevButton = () => (
	<SVG
		xmlns="http://www.w3.org/2000/svg"
		width="49"
		height="48"
		viewBox="0 0 49 48"
		fill="none"
		className={ `wc-block-product-gallery-large-image-next-previous-left` }
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
