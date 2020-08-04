/**
 * External dependencies
 */
import { render, Suspense } from '@wordpress/element';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';

/**
 * Renders a block component in the place of a specified set of selectors.
 *
 * @param {Object}   props                         Render props.
 * @param {Function} props.Block                   React component to use as a replacement.
 * @param {string}   props.selector                CSS selector to match the elements to replace.
 * @param {Function} [props.getProps ]             Function to generate the props object for the block.
 * @param {Function} [props.getErrorBoundaryProps] Function to generate the props object for the error boundary.
 */
export const renderFrontend = ( {
	Block,
	selector,
	getProps = () => {},
	getErrorBoundaryProps = () => {},
} ) => {
	const containers = document.querySelectorAll( selector );

	if ( containers.length ) {
		// @todo Remove Suspense compatibility fix once WP 5.2 is no longer supported.
		// If Suspense is not available (WP 5.2), use a noop component instead.
		const noopComponent = ( { children } ) => {
			return <>{ children }</>;
		};
		const SuspenseComponent = Suspense || noopComponent;

		// Use Array.forEach for IE11 compatibility.
		Array.prototype.forEach.call( containers, ( el, i ) => {
			const props = getProps( el, i );
			const errorBoundaryProps = getErrorBoundaryProps( el, i );
			const attributes = {
				...el.dataset,
				...props.attributes,
			};
			el.classList.remove( 'is-loading' );

			render(
				<BlockErrorBoundary { ...errorBoundaryProps }>
					<SuspenseComponent
						fallback={ <div className="wc-block-placeholder" /> }
					>
						<Block { ...props } attributes={ attributes } />
					</SuspenseComponent>
				</BlockErrorBoundary>,
				el
			);
		} );
	}
};

export default renderFrontend;
