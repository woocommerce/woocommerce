/**
 * External dependencies
 */
import { render } from 'react-dom';
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

export default renderFrontend;
