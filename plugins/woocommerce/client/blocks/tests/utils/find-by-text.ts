/**
 * External dependencies
 */
import type { MatcherFunction } from '@testing-library/react';

/**
 * This function will match text over several elements, the standard matcher
 * will only find strings if they are within the same element.
 */
export const textContentMatcher = ( text: string ): MatcherFunction => {
	return ( _content, node ) => {
		const hasText = ( _node ) => _node.textContent?.includes( text );
		const nodeHasText = hasText( node );
		const childrenDontHaveText = Array.from( node?.children || [] ).every(
			( child ) => ! hasText( child )
		);
		return nodeHasText && childrenDontHaveText;
	};
};

/**
 * This will check if the text is present an the container, it can be within
 * multiple elements, for example:
 * <div>
 *     <span>Text</span>
 *     <span>is</span>
 *     <span>present</span>
 * </div>
 */
export const textContentMatcherAcrossSiblings = (
	text: string
): MatcherFunction => {
	return ( _content, node ): boolean => {
		/*
		If the element in question is not the first child, then skip, as we
		will have already run this check for its siblings (when we ran it on the
		first child).
		*/
		const siblings =
			node?.parentElement?.children[ 0 ] === node
				? node?.parentElement?.children
				: [];
		let siblingText = '';

		// Get the text of all siblings and put it into a single string.
		if ( siblings?.length > 0 ) {
			siblingText = Array.from( siblings )
				.map( ( child ) => child.textContent )
				.filter( Boolean )
				.join( ' ' )
				.trim();
		}
		return siblingText !== '' && siblingText === text;
	};
};
