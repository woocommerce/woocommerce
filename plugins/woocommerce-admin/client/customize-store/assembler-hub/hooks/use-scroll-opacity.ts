/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

type ScrollDirection = 'topDown' | 'bottomUp';

export const useScrollOpacity = (
	selector: string,
	direction: ScrollDirection = 'topDown',
	sensitivity = 0.2
) => {
	const [ opacity, setOpacity ] = useState( 0.05 );

	useEffect( () => {
		const targetElement = document.querySelector( selector );

		const handleScroll = () => {
			if ( targetElement ) {
				const maxScrollHeight =
					targetElement.scrollHeight - targetElement.clientHeight;
				const currentScrollPosition = targetElement.scrollTop;
				const maxEffectScroll = maxScrollHeight * sensitivity;

				let calculatedOpacity;
				if ( direction === 'bottomUp' ) {
					calculatedOpacity =
						1 - currentScrollPosition / maxEffectScroll;
				} else {
					calculatedOpacity = currentScrollPosition / maxEffectScroll;
				}

				calculatedOpacity = 0.1 + 0.9 * calculatedOpacity;

				// Clamp opacity between 0.1 and 1
				calculatedOpacity = Math.max(
					0.1,
					Math.min( calculatedOpacity, 1 )
				);

				setOpacity( calculatedOpacity );
			}
		};

		if ( targetElement ) {
			targetElement.addEventListener( 'scroll', handleScroll );
		}

		return () => {
			if ( targetElement ) {
				targetElement.removeEventListener( 'scroll', handleScroll );
			}
		};
	}, [ selector, direction, sensitivity ] );

	return opacity;
};
