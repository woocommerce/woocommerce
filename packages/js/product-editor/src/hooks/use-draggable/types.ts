export type DraggableProps< T > = {
	onSort( fnState: ( items: T[] ) => T[] ): void;
};
