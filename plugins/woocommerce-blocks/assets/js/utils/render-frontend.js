/**
 * External dependencies
 */
import { render } from 'react-dom';
import BlockErrorBoundary from '@woocommerce/base-components/block-error-boundary';

/**
 * Renders a block component in the place of a specified set of selectors.
 *
 * @param {string}   selector                CSS selector to match the elements
 * to replace.
 * @param {Function} Block                   React block to use as a replacement.
 * @param {Function} [getProps]              Function to generate the props
 * object for the block.
 * @param {Function} [getErrorBoundaryProps] Function to generate the props
 * object for the error boundary.
 */
export default (
	selector,
	Block,
	getProps = () => {},
	getErrorBoundaryProps = () => {}
) => {
	const containers = document.querySelectorAll( selector );

	if ( containers.length ) {
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
					<Block { ...props } attributes={ attributes } />
				</BlockErrorBoundary>,
				el
			);
		} );
	}
};
