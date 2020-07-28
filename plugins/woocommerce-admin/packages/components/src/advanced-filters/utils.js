/**
 * External dependencies
 */
import { isArray, isNumber, isString } from 'lodash';

/**
 * @typedef {Object} ReactNode
 * @property
 */

/**
 * DOM Node.textContent for React components
 * See: https://github.com/rwu823/react-addons-text-content/blob/master/src/index.js
 *
 * @param {Array<string|ReactNode>} components array of components
 *
 * @return {string} concatenated text content of all nodes
 */ export function textContent( components ) {
	let text = '';

	const toText = ( component ) => {
		if ( isString( component ) || isNumber( component ) ) {
			text += component;
		} else if ( isArray( component ) ) {
			component.forEach( toText );
		} else if ( component && component.props ) {
			const { children } = component.props;

			if ( isArray( children ) ) {
				children.forEach( toText );
			} else {
				toText( children );
			}
		}
	};

	toText( components );

	return text;
}
