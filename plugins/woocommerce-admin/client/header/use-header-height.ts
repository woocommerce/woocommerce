/**
 * External dependencies
 */
import { useEffect, useLayoutEffect, useState } from '@wordpress/element';

export const useHeaderHeight = () => {
	const [ headerHeight, setHeaderHeight ] = useState( 60 );
	const [ adminBarHeight, setAdminBarHeight ] = useState( 32 );

	useEffect( () => {
		const wpbody = document.querySelector( '#wpbody' ) as Node;
		const observer = new MutationObserver( () => {
			setHeaderHeight(
				parseInt( ( wpbody as HTMLElement ).style.marginTop, 10 )
			);
		} );
		observer.observe( wpbody, {
			attributes: true,
		} );

		return () => {
			observer.disconnect();
		};
	}, [] );

	useLayoutEffect( () => {
		const handleResize = () => {
			const adminBar = document.querySelector(
				'#wpadminbar'
			) as HTMLElement;
			setAdminBarHeight( adminBar.clientHeight );
		};
		window.addEventListener( 'resize', handleResize );
		return () => {
			window.removeEventListener( 'resize', handleResize );
		};
	}, [] );

	return {
		adminBarHeight,
		headerHeight,
	};
};
