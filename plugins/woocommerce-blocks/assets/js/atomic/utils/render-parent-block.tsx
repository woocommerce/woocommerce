/**
 * External dependencies
 */
import { renderFrontend } from '@woocommerce/base-utils';
import {
	Fragment,
	Suspense,
	cloneElement,
	isValidElement,
} from '@wordpress/element';
import parse from 'html-react-parser';

interface renderBlockProps {
	// Parent Block Name. Used for inner block component mapping.
	blockName: string;
	// Map of block names to block components for children.
	blockMap: Record< string, React.ReactNode >;
	// Wrapper for inner components.
	blockWrapper?: React.ReactNode;
}

interface renderParentBlockProps extends renderBlockProps {
	// React component to use as a replacement.
	Block: React.FunctionComponent;
	// CSS selector to match the elements to replace.
	selector: string;
	// Function to generate the props object for the block.
	getProps: ( el: Element, i: number ) => Record< string, unknown >;
}

interface renderInnerBlockProps extends renderBlockProps {
	children: HTMLCollection;
	depth?: number;
}

/**
 * Replaces saved block HTML markup with Inner Block Components.
 */
const renderInnerBlocks = ( {
	blockName: parentBlockName,
	blockMap,
	blockWrapper,
	depth = 1,
	children,
}: renderInnerBlockProps ): ( JSX.Element | null )[] | null => {
	return Array.from( children ).map( ( el: Element, index: number ) => {
		const { blockName = '', ...componentProps } = {
			key: `${ parentBlockName }_${ depth }_${ index }`,
			...( el instanceof HTMLElement ? el.dataset : {} ),
		};

		const componentChildren =
			el.children && el.children.length
				? renderInnerBlocks( {
						children: el.children,
						blockName: parentBlockName,
						blockMap,
						depth: depth + 1,
						blockWrapper,
				  } )
				: null;

		const LayoutComponent =
			blockName && blockMap[ blockName ]
				? ( blockMap[ blockName ] as React.ElementType )
				: null;

		if ( ! LayoutComponent ) {
			const element = parse( el.outerHTML );

			if ( isValidElement( element ) ) {
				return componentChildren
					? cloneElement( element, componentProps, componentChildren )
					: cloneElement( element, componentProps );
			}
			return null;
		}

		const LayoutComponentWrapper = ( blockWrapper
			? blockWrapper
			: Fragment ) as React.ElementType;

		return (
			<Suspense
				key={ `${ parentBlockName }_${ depth }_${ index }_suspense` }
				fallback={ <div className="wc-block-placeholder" /> }
			>
				<LayoutComponentWrapper>
					<LayoutComponent { ...componentProps }>
						{ componentChildren }
					</LayoutComponent>
				</LayoutComponentWrapper>
			</Suspense>
		);
	} );
};

/**
 * Renders a block component in the place of a specified set of selectors.
 */
export const renderParentBlock = ( {
	Block,
	selector,
	blockName,
	getProps = () => ( {} ),
	blockMap,
	blockWrapper,
}: renderParentBlockProps ): void => {
	const getPropsWithChildren = ( el: Element, i: number ) => {
		const children =
			el.children && el.children.length
				? renderInnerBlocks( {
						blockName,
						blockMap,
						children: el.children,
						blockWrapper,
				  } )
				: null;
		return { ...getProps( el, i ), children };
	};
	renderFrontend( {
		Block,
		selector,
		getProps: getPropsWithChildren,
	} );
};
