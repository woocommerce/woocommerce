/**
 * JSON from a data attribute. A missing or unreadable value is null, and this
 * never throws, so one bad payload cannot stop the next one from being read.
 *
 * @param value Attribute value, missing when the attribute is absent.
 */
export function readJsonDataset( value: string | undefined ): unknown {
	try {
		return JSON.parse( value ?? 'null' );
	} catch {
		return null;
	}
}
