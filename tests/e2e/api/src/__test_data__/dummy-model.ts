import { Model } from '../models';

/**
 * A dummy model that can be used in test files.
 */
export class DummyModel extends Model {
	public name: string = '';

	public constructor( partial?: Partial< DummyModel > ) {
		super();
		Object.assign( this, partial );
	}
}
