/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import { useInstanceId } from '@wordpress/compose';

export function Pants() {
	const clipPathId = useInstanceId( Pants, 'pants' ) as string;
	return (
		<svg
			width="50"
			height="72"
			viewBox="0 0 50 72"
			fill="none"
			xmlns="http://www.w3.org/2000/svg"
		>
			<g clipPath={ `url(#${ clipPathId })` }>
				<path
					d="M44.6084 21.3845C40.788 21.6427 35.5059 20.8456 35.1404 16.333C34.8746 13.0889 34.5867 9.04771 34.3431 5.7811H42.9474L42.3273 0H8.34205L7.72192 5.7811H16.3262C16.0826 9.04771 15.8057 13.0889 15.5289 16.333C15.1635 20.8456 9.87022 21.6314 6.06086 21.3845L0.667969 72H14.0007C14.0007 72 21.7745 32.0711 22.904 26.0318C23.4909 22.9111 24.3989 22.2264 25.3291 22.2264C26.2593 22.2264 27.1673 22.9224 27.7543 26.0318C28.8948 32.0599 36.6575 72 36.6575 72H49.9903L44.5974 21.3845H44.6084Z"
					fill="#F0F0F0"
				/>
				<path
					d="M15.5383 16.3332C15.8041 13.089 16.092 9.04785 16.3356 5.78125H7.73137L6.07031 21.3846C9.89074 21.6428 15.1729 20.8458 15.5383 16.3332Z"
					fill="#DDDDDD"
				/>
				<path
					d="M35.1293 16.3332C35.4948 20.8458 40.788 21.6316 44.5974 21.3846L42.9363 5.78125H34.332C34.5757 9.04785 34.8525 13.089 35.1293 16.3332Z"
					fill="#DDDDDD"
				/>
			</g>
			<defs>
				<clipPath id={ clipPathId }>
					<rect
						width="49.3334"
						height="72"
						fill="white"
						transform="translate(0.667969)"
					/>
				</clipPath>
			</defs>
		</svg>
	);
}
