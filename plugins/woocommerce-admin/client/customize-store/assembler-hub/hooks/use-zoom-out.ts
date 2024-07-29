/* eslint-disable @woocommerce/dependency-group */
/**
 * External dependencies
 */
import { useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { useQuery } from '@woocommerce/navigation';

const allowedUrls = [ '/customize-store/assembler-hub/homepage/' ];

function isPathIncluded( currentPath: string, urls: string[] ) {
	if ( ! currentPath ) {
		return false;
	}

	return urls.some( ( url ) => currentPath.startsWith( url ) );
}

export const useZoomOut = () => {
	const [ isZoomedOut, setIsZoomedOut ] = useState( false );
	const params = useQuery();

	useEffect( () => {
		if ( isPathIncluded( params.path, allowedUrls ) ) {
			setIsZoomedOut( true );
		} else {
			setIsZoomedOut( false );
		}
	}, [ params.path ] );

	return {
		isZoomedOut,
		setIsZoomedOut,
	};
};
