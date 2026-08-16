/**
 * This module contains a set of utility functions to handle style variables and their compressed format.
 * The compressed format is used to store style variables in the database and in the editor settings.
 * The uncompressed format is used when a variable is used in CSS.
 */

// Utility to normalize compressed preset var:preset:<type>|<value> variables to --wp--preset--<type>--<value> format
export function unwrapCompressedPresetStyleVariableName(
	value: string
): string | null {
	if ( typeof value !== 'string' ) return null;
	const match = value.match(
		/^var:preset\|([a-zA-Z0-9-]+)\|([a-zA-Z0-9-]+)$/
	);
	return match ? `--wp--preset--${ match[ 1 ] }--${ match[ 2 ] }` : null;
}

// Utility to normalize compressed preset var:preset:<type>|<value> variables to var(--wp--preset--<type>--<value>) format
export function unwrapCompressedPresetStyleVariable( value: string ): string {
	const variableName = unwrapCompressedPresetStyleVariableName( value );
	return variableName ? `var(${ variableName })` : value;
}

// Get the raw value of a compressed variable read from the root element
export function getCompressedVariableValue( value: string ): string {
	const variableName = unwrapCompressedPresetStyleVariableName( value );
	if ( ! variableName ) return value;
	const root = document.querySelector( ':root' );
	if ( ! root ) return value;
	const computedStyle = getComputedStyle( root );
	return computedStyle.getPropertyValue( variableName ).trim() || value;
}
