/**
 * Internal dependencies
 */
import type { SettingsRecord } from './screen';

/**
 * Pick the edits to the given fields.
 */
export function pickEdits(
	edits: SettingsRecord,
	fieldIds: string[]
): SettingsRecord {
	return Object.fromEntries(
		Object.entries( edits ).filter( ( [ key ] ) =>
			fieldIds.includes( key )
		)
	);
}
