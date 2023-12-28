/**
 * Extracts the built-in color from a block class name string if it exists.
 * Returns null if no built-in color is found.
 *
 * @param  blockClassString The block class name string.
 * @return {string|null} The color name or null if no built-in color is found.
 */
export const extractBuiltInColor = ( blockClassString: string ) => {
	const regex = /has-(?!link|text|background)([a-z-]+)-color/;
	const match = blockClassString.match( regex );
	return match ? match[ 1 ] : null;
};
