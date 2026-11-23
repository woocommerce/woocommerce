/**
 * JSON from a data attribute. A missing or unreadable value is null, and this
 * never throws, so one bad payload cannot stop the next one from being read.
 *
 * @param value Attribute value, missing when the attribute is absent.
 */
export function readJsonDataset< T = unknown >(
	value: string | undefined
): T | null {
	try {
		return JSON.parse( value ?? 'null' ) as T;
	} catch {
		return null;
	}
}
