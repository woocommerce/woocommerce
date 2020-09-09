/**
 * A base class for all models.
 */
export abstract class Model {
	private _id: number | null = null;

	public get id(): number | null {
		return this._id;
	}

	public onCreated( data: any ): void {
		this._id = data.id;
	}
}
