/**
 * External dependencies
 */
import { useInstanceId } from '@wordpress/compose';
import { createElement } from '@wordpress/element';

export function ProductTShirt( props: React.SVGProps< SVGSVGElement > ) {
	const clipPathId = useInstanceId( ProductTShirt, 'clip-path' ) as string;

	return (
		<svg
			{ ...props }
			viewBox="0 0 56 56"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
			aria-hidden="true"
			focusable={ false }
		>
			<g clipPath={ `url(#${ clipPathId })` }>
				<path
					d="M18.7261 9.37008H26.8168V5.47626H28.4106C29.4938 5.47626 29.9499 4.92889 29.9499 3.91198C29.9499 2.89508 29.4938 2.34771 28.4106 2.34771C27.8689 2.34771 25.6325 2.32955 25.6325 2.32955V0L28.9263 0.0181591C31.2664 0.0181591 32.6244 1.59022 32.6244 3.91198C32.6244 6.23375 31.339 7.72539 29.1206 7.811V9.37008H37.2761C37.2761 9.37008 46.6289 13.7438 46.6289 14.0136H9.31112C9.31112 13.7438 18.7287 9.37008 18.7287 9.37008H18.7261Z"
					fill="#F0F0F0"
				/>
				<path
					d="M0 21.0152C0 21.0152 9.19987 12.1613 10.6356 11.0484C11.8717 10.0912 13.3826 9.34668 16.3213 9.34668H18.7263C19.0943 14.2315 23.023 18.076 28.0013 18.076C32.9796 18.076 36.9083 14.2315 37.2763 9.34668H39.6812C42.62 9.34668 44.1309 10.0886 45.367 11.0484C46.8001 12.1613 56 21.0152 56 21.0152L52.8202 30.3541H44.3822L44.39 56.0025H11.6074L11.6152 30.3541H3.17719L-0.00259399 21.0152H0Z"
					fill="currentColor"
				/>
			</g>
			<defs>
				<clipPath id={ clipPathId }>
					<rect
						width="56"
						height="56"
						fill="white"
						transform="matrix(-1 0 0 1 56 0)"
					/>
				</clipPath>
			</defs>
		</svg>
	);
}
