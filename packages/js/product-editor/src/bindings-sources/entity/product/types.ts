export type WooEntitySourceArgs = {
	/*
	 * The kind of entity to bind.
	 * Default is `postType`.
	 */
	kind?: string;

	/*
	 * The name of the entity to bind.
	 */
	name?: string;

	/*
	 * The name of the entity property to bind.
	 */
	prop: string;

	/*
	 * The ID of the entity to bind.
	 */
	id?: string;
};
