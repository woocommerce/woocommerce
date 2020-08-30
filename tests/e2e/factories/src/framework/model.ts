import { DeepPartial } from 'fishery';

/**
 * A base class for all models.
 */
export abstract class Model {
	private _id: number = 0;

	protected constructor( partial: DeepPartial<any> = {} ) {
		Object.assign( this, partial );
	}

	public get id(): number {
		return this._id;
	}

	public setID( id: number ): void {
		this._id = id;
	}
}
