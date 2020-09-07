import { RepositoryData } from '../framework/repository';

/**
 * A base class for all models.
 */
export class Model implements RepositoryData {
	private _id: number | null = null;

	public get id(): number | null {
		return this._id;
	}

	public onCreated( data: any ): void {
		this._id = data.id;
	}
}
