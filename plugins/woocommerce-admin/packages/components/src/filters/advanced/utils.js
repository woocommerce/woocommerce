/**
 * External dependencies
 */
import { isArray, isString } from 'lodash';

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
