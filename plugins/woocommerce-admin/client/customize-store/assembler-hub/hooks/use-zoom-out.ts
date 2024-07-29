/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import { useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */
import { useQuery } from '@woocommerce/navigation';

const allowedUrls = [ '/customize-store/assembler-hub/homepage/' ];

function isPathIncluded( currentPath: string, urls: string[] ) {
	return urls.some( ( url ) => currentPath.startsWith( url ) );
}

export const useZoomOut = ( {
	documentElement,
}: {
	documentElement: HTMLElement | null;
} ) => {
	const params = useQuery();

	useEffect( () => {
		if ( ! documentElement ) {
			return;
		}

		if ( isPathIncluded( params.path, allowedUrls ) ) {
			documentElement.classList.add( 'is-zoomed-out' );
		} else {
			documentElement.classList.remove( 'is-zoomed-out' );
		}
	}, [ params.path, documentElement ] );
};
