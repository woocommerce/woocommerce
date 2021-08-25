/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import preloadScript from '@woocommerce/base-utils/preload-script';
import lazyLoadScript from '@woocommerce/base-utils/lazy-load-script';

interface dependencyData {
	src: string;
	version?: string;
	after?: string;
	before?: string;
	translations?: string;
}

// eslint-disable-next-line @wordpress/no-global-event-listener
window.onload = () => {
	const miniCartBlocks = document.querySelectorAll( '.wc-block-mini-cart' );

	if ( miniCartBlocks.length === 0 ) {
		return;
	}

	const dependencies = getSetting(
		'mini_cart_block_frontend_dependencies',
		{}
	) as Record< string, dependencyData >;

	// Preload scripts
	for ( const dependencyHandle in dependencies ) {
		const dependency = dependencies[ dependencyHandle ];
		preloadScript( {
			handle: dependencyHandle,
			...dependency,
		} );
	}

	miniCartBlocks.forEach( ( miniCartBlock ) => {
		const miniCartButton = miniCartBlock.querySelector(
			'.wc-block-mini-cart__button'
		);
		const miniCartContents = miniCartBlock.querySelector(
			'.wc-block-mini-cart__contents'
		);

		if ( ! miniCartButton || ! miniCartContents ) {
			// Markup is not correct, abort.
			return;
		}

		const showContents = async () => {
			miniCartContents.removeAttribute( 'hidden' );

			// Load scripts
			for ( const dependencyHandle in dependencies ) {
				const dependency = dependencies[ dependencyHandle ];
				await lazyLoadScript( {
					handle: dependencyHandle,
					...dependency,
				} );
			}
		};
		const hideContents = () =>
			miniCartContents.setAttribute( 'hidden', 'true' );

		miniCartButton.addEventListener( 'mouseover', showContents );
		miniCartButton.addEventListener( 'mouseleave', hideContents );

		miniCartContents.addEventListener( 'mouseover', showContents );
		miniCartContents.addEventListener( 'mouseleave', hideContents );

		miniCartButton.addEventListener( 'focus', showContents );
		miniCartButton.addEventListener( 'blur', hideContents );
	} );
};
