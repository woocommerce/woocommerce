/**
 * Parse an array of items into an ordering object.
 *
 * @param items - The items to parse.
 * @return The ordering object.
 */
export const parseOrdering = < T extends { id: string | number } >(
	items: T[]
) => {
	return items.reduce( ( acc, item, index ) => {
		return { ...acc, [ item.id ]: index };
	}, {} );
};
