/**
 * External dependencies
 */
import deepmerge from 'deepmerge';
import { useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { storeName, TypographyProperties } from '../store';
import { useUserTheme } from './use-user-theme';

interface ElementProperties {
	typography: TypographyProperties;
}
export interface StyleProperties {
	spacing?: {
		padding: {
			top: string;
			right: string;
			bottom: string;
			left: string;
		};
		blockGap: string;
	};
	typography?: TypographyProperties;
	color?: {
		background: string;
		text: string;
	};
	elements?: Record< string, ElementProperties >;
}

interface EmailStylesData {
	styles: StyleProperties;
	userStyles: StyleProperties;
	defaultStyles: StyleProperties;
	updateStyleProp: ( path, newValue ) => void;
	updateStyles: ( newStyles: StyleProperties ) => void;
}

/**
 * Immutably sets a value inside an object. Like `lodash#set`, but returning a
 * new object. Treats nullish initial values as empty objects. Clones any
 * nested objects. Supports arrays, too.
 *
 * @param                       setObject
 * @param {number|string|Array} setPath   Path in the object to modify.
 * @param {*}                   value     New value to set.
 * @return {Object} Cloned object with the new value set.
 */
export function setImmutably( setObject, setPath, value ): typeof setObject {
	// Normalize path
	const path = Array.isArray( setPath ) ? [ ...setPath ] : [ setPath ];

	// Shallowly clone the base of the object
	const object = Array.isArray( setObject )
		? [ ...setObject ]
		: { ...setObject };

	const leaf = path.pop();

	// Traverse object from root to leaf, shallowly cloning at each level
	let prev = object;

	path.forEach( ( key ) => {
		const lvl = prev[ key ];
		prev[ key ] = Array.isArray( lvl ) ? [ ...lvl ] : { ...lvl };
		prev = prev[ key ];
	} );

	prev[ leaf ] = value;

	return object;
}

/**
 * Shorten the variable names in the styles object. Some components need the h
 * Transforms variables like var(--wp--preset--spacing--10) to var:preset|spacing|10
 *
 * @param {Object} obj The object to shorten the variable names in.
 * @return {Object} The object with the shortened variable names.
 */
function shortenWpPresetVariables( obj ) {
	// Helper function to replace the variable string
	const replaceVariable = ( value ) => {
		return value.replace(
			/var\(--([a-z]+)--([a-z]+(?:--[a-z0-9]+(?:-[a-z0-9]+)*)*)--([a-z0-9-]+)\)/g,
			( _match, _prefix, group1, group2 ) => {
				const groups = group1.split( '--' ).concat( group2 );
				return `var:${ groups.join( '|' ) }`;
			}
		);
	};

	// Recursive function to traverse the object
	const traverse = ( current ) => {
		if ( typeof current === 'object' && current !== null ) {
			for ( const key in current ) {
				if ( current.hasOwnProperty( key ) ) {
					current[ key ] = traverse( current[ key ] );
				}
			}
		} else if ( typeof current === 'string' ) {
			return replaceVariable( current );
		}
		return current;
	};

	return traverse( obj );
}

/**
 * Remove empty arrays and keys with undefined values from an object.
 * Empty values causes issues with deepmerge, because it can overwrite default value we provide in base email theme.
 *
 * @param {Object} obj The object to clean.
 * @return {Object} The cleaned object.
 */
function cleanupUserStyles( obj ) {
	const cleanObject = ( current ) => {
		if (
			( typeof current === 'object' && current !== null ) ||
			current === undefined
		) {
			if ( Array.isArray( current ) && current.length === 0 ) {
				return undefined; // Remove empty arrays
			}

			for ( const key in current ) {
				if ( current.hasOwnProperty( key ) ) {
					const cleanedValue = cleanObject( current[ key ] );
					if ( cleanedValue === undefined ) {
						delete current[ key ]; // Remove keys with undefined values
					} else {
						current[ key ] = cleanedValue;
					}
				}
			}
		}
		return current;
	};

	return cleanObject( obj );
}

export const useEmailStyles = (): EmailStylesData => {
	// const { templateTheme, updateTemplateTheme } = useEmailTheme();
	const { userTheme, updateUserTheme } = useUserTheme();

	// This is email level styling stored in post meta.
	const styles = useMemo(
		() =>
			cleanupUserStyles( shortenWpPresetVariables( userTheme?.styles ) ),
		[ userTheme ]
	);

	// Default styles from theme.json.
	const { styles: defaultStyles } = useSelect( ( select ) => ( {
		styles: select( storeName ).getStyles(),
	} ) );

	// Update email styles.
	const updateStyles = useCallback(
		( newStyles ) => {
			const newTheme = {
				...userTheme,
				styles: cleanupUserStyles( newStyles ),
			};
			updateUserTheme( newTheme );
		},
		[ updateUserTheme, userTheme ]
	);

	// Update an email style prop.
	const updateStyleProp = useCallback(
		( path, newValue ) => {
			const newTheme = setImmutably(
				userTheme,
				[ 'styles', ...path ],
				newValue
			);
			updateUserTheme( newTheme );
		},
		[ updateUserTheme, userTheme ]
	);

	return {
		styles: deepmerge.all( [ defaultStyles || {}, styles || {} ] ), // Merged styles
		userStyles: userTheme?.styles, // Styles defined by user
		defaultStyles, // Default styles from editors theme.json
		updateStyleProp,
		updateStyles,
	};
};
