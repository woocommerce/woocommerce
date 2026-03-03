/**
 * External dependencies
 */
import { registerFormatType, unregisterFormatType } from '@wordpress/rich-text';

// Based on import('./register-format-type').WPFormat
type WPFormat = {
	name: string;
	tagName: string;
	interactive: boolean;
	title: string;
	// WPFormat.edit uses Function which is not allowed in type definitions
	// eslint-disable-next-line @typescript-eslint/no-explicit-any
	edit: any;
	className?: string;
	attributes?: Record< string, string >;
};

// Registry to track changes applied by the email editor
const newlyRegisteredFormats = new Set< string >();
const preservedUnregisteredFormats = new Map< string, WPFormat >();

/**
 * Registers a format and records it for potential cleanup.
 * If the format already exists, it will be replaced and the previous definition will be preserved.
 */
export function registerFormatForEmail(
	name: string,
	settings: WPFormat
): void {
	registerFormatType( name, settings );
	newlyRegisteredFormats.add( name );
}

/**
 * Unregisters a format, preserving its current definition so it can be restored later.
 */
export function unregisterFormatForEmail( name: string ): void {
	const previous = unregisterFormatType( name );
	if ( previous ) {
		preservedUnregisteredFormats.set( name, previous );
	}
}

/**
 * Restores formats to the state before email-editor changes:
 * - Re-register all formats that we unregistered and preserved
 * - Remove formats that were registered by the email editor
 * The order is: remove newly registered first, then restore preserved definitions.
 */
export function resetFormats(): void {
	// Remove formats introduced by the email editor
	for ( const name of newlyRegisteredFormats ) {
		unregisterFormatType( name );
	}
	newlyRegisteredFormats.clear();

	// Restore preserved formats
	for ( const [ name, format ] of preservedUnregisteredFormats.entries() ) {
		registerFormatType( name, format );
		preservedUnregisteredFormats.delete( name );
	}
}
