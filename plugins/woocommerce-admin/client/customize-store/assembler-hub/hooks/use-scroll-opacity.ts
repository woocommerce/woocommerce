/* eslint-disable @woocommerce/dependency-group */
/* eslint-disable @typescript-eslint/ban-ts-comment */
/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';
// @ts-ignore No types for this exist yet.
import { useIsSiteEditorLoading } from '@wordpress/edit-site/build-module/components/layout/hooks';

type ScrollDirection = 'topDown' | 'bottomUp';

export const useScrollOpacity = (
	selector: string,
	direction: ScrollDirection = 'topDown',
	sensitivity = 0.2
) => {
	const [ opacity, setOpacity ] = useState( 0.05 );
	const isEditorLoading = useIsSiteEditorLoading();

	useEffect( () => {
		let targetElement: Document | Element | null =
			document.querySelector( selector );

		const isIFrame = targetElement?.tagName === 'IFRAME';
		if ( isIFrame ) {
			targetElement = ( targetElement as HTMLIFrameElement )
				.contentDocument;
		}

		const handleScroll = () => {
			if ( ! targetElement ) {
				return;
			}

			const contentElement = isIFrame
				? ( targetElement as Document ).documentElement
				: ( targetElement as Element );

			const _sensitivity =
				// Set sensitivity to a small threshold for mobile devices because they have a small viewport to ensure the effect is visible.
				contentElement.clientWidth > 480 ? sensitivity : 0.05;

			const maxScrollHeight =
				contentElement.scrollHeight - contentElement.clientHeight;
			const currentScrollPosition = contentElement.scrollTop;
			const maxEffectScroll = maxScrollHeight * _sensitivity;

			let calculatedOpacity;
			if ( direction === 'bottomUp' ) {
				calculatedOpacity =
					maxScrollHeight / maxEffectScroll -
					currentScrollPosition / maxEffectScroll;
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
		};

		if ( targetElement ) {
			targetElement.addEventListener( 'scroll', handleScroll );
		}

		return () => {
			if ( targetElement ) {
				targetElement.removeEventListener( 'scroll', handleScroll );
			}
		};
	}, [ selector, direction, sensitivity, isEditorLoading ] );

	return opacity;
};
