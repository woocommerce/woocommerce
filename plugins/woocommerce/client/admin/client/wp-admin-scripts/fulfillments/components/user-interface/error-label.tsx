/**
 * External dependencies
 */
import { useLayoutEffect, useRef } from 'react';

export default function ErrorLabel( { error }: { error: string } ) {
	const labelRef = useRef< HTMLDivElement >( null );
	useLayoutEffect( () => {
		if ( error ) {
			// Scroll to the top of the error label when an error occurs.
			labelRef.current?.scrollIntoView( {
				behavior: 'smooth',
				block: 'start',
				inline: 'nearest',
			} );
		}
	}, [ error ] );
	return (
		<div className="woocommerce-fulfillment-error-label" ref={ labelRef }>
			<span className="woocommerce-fulfillment-error-label__icon">
				<svg
					width="16"
					height="16"
					viewBox="0 0 16 16"
					fill="none"
					xmlns="http://www.w3.org/2000/svg"
				>
					<path
						d="M7.99996 13.3333C10.9455 13.3333 13.3333 10.9455 13.3333 7.99996C13.3333 5.05444 10.9455 2.66663 7.99996 2.66663C5.05444 2.66663 2.66663 5.05444 2.66663 7.99996C2.66663 10.9455 5.05444 13.3333 7.99996 13.3333Z"
						strokeWidth="1.5"
					/>
					<path d="M8.66671 4.66663H7.33337V8.66663H8.66671V4.66663Z" />
					<path d="M8.66671 10H7.33337V11.3333H8.66671V10Z" />
				</svg>
			</span>
			<span className="woocommerce-fulfillment-error-label__text">
				{ error }
			</span>
		</div>
	);
}
