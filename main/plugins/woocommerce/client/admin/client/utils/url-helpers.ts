/**
 * Extracts all segments from the path query param as a string array.
 *
 * @param path The query path param
 * @return The list of segments from the path
 */
export function getSegmentsFromPath( path?: string ): string[] {
	const firstIndex = path?.startsWith( '/' ) ? 1 : 0;
	const lastIndex = path?.endsWith( '/' ) ? -1 : undefined;
	return path?.slice( firstIndex, lastIndex )?.split( '/' ) ?? [];
}
