/**
 * External dependencies
 */
import type { Field } from '@wordpress/dataviews';

/**
 * The JavaScript parts of a collection's fields, keyed by field id. It is the shape of a field
 * collection's script module default export, which the editor merges into the server's fields.
 */
export type FieldsScriptParts< Item > = Record<
	string,
	Partial< Omit< Field< Item >, 'id' > >
>;
