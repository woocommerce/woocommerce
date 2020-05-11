export abstract class Model {
	private id: number | null = null;

	/**
	 * Fetches the model's ID.
	 *
	 * @return {number|null} The ID of the model or null if it has not yet been created.
	 */
	public get ID(): number | null {
		return this.id;
	}

	/**
	 * A hook to be called when the model is created, so that server-generated data can be stored.
	 *
	 * @param {number} id
	 */
	public onCreated( id: number ): void {
		if ( null !== this.id ) {
			throw new Error( 'The model has already been created!' );
		}

		this.id = id;
	}
}
