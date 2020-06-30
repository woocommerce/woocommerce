/**
 * A base class for all models.
 */
export abstract class Model {
	private _id: number = 0;

	public constructor( partial?: Partial<Model> ) {
		if ( partial ) {
			Object.assign( this, partial );
		}
	}

	public get id(): number {
		return this._id;
	}

	/**
	 * A handler to be called after the model has been created.
	 *
	 * @param {*} created The model that was created on the server.
	 */
	public onCreated( created: any ): void {
		if ( this._id ) {
			throw new Error( 'The model has been created already.' );
		}

		this._id = created.id;
	}
}
