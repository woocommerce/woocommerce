/**
 * External dependencies
 */
import { isArray, isNumber, isString } from 'lodash';
import deprecated from '@wordpress/deprecated';

/**
 * DOM Node.textContent for React components
 * See: https://github.com/rwu823/react-addons-text-content/blob/master/src/index.js
 *
 * @param {Array<string|Node>} components array of components
 *
 * @return {string} concatenated text content of all nodes
 */
export function textContent( components ) {
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

/**
 * This function processes an input string, checks for deprecated interpolation formatting, and
 * modifies it to conform to the new standard.
 * The deprecated interpolation formatting is `{{element}}...{{/element}}`, and the new standard
 * formatting is `<element>...</element>`.
 *
 * @param {string} interpolatedString The interpolation string to be parsed.
 *
 * @return {string}  Fixed interpolation string.
 */
export function getInterpolatedString( interpolatedString ) {
	const regex = /(\{\{)(\/?\s*\w+\s*\/?)(\}\})/g;

	if ( regex.exec( interpolatedString ) !== null ) {
		deprecated(
			'The interpolation string format `{{element}}...{{/element}}` or `{{element/}}` is deprecated.',
			{
				version: '7.8.0',
				hint: 'Please update your code to use the new format: `<element>...</element>` or `<element/>`.',
			}
		);

		return interpolatedString.replaceAll( regex, ( match, p1, p2 ) => {
			const inner = p2.trim();
			let replacement;
			if ( inner.startsWith( '/' ) ) {
				// Closing tag
				replacement = `</${ inner.slice( 1 ) }>`;
			} else if ( inner.endsWith( '/' ) ) {
				// Self-closing tag
				replacement = `<${ inner.slice( 0, -1 ) }/>`;
			} else {
				// Opening tag
				replacement = `<${ inner }>`;
			}

			return replacement;
		} );
	}

	return interpolatedString;
}
