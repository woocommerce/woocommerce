export type UseSelectionProps< T > = {
	getId( item: T ): string;
};

export type Selection< T > = {
	[ itemId: string ]: T | undefined;
};
