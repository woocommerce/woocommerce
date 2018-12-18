/**
 * External dependencies
 */
import { isArray, isNumber, isString } from 'lodash';

/**
 * Hide all bare strings from the accessibility tree.
 * Intended to process the result of interpolateComponents().
 *
 * @param {Array<string|ReactNode>} components array of components
 *
 * @returns {Array<string|ReactNode>} array of processed components
 */
export function ariaHideStrings( components ) {
    if ( ! isArray( components ) ) {
        return components;
    }

    return components.map( component => (
        isString( component ) && component.trim()
        ? <span aria-hidden>{ component }</span>
        : component
    ) );
}

/**
 * DOM Node.textContent for React components
 * See: https://github.com/rwu823/react-addons-text-content/blob/master/src/index.js
 *
 * @param {Array<string|ReactNode>} components array of components
 *
 * @returns {string} concatenated text content of all nodes
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
