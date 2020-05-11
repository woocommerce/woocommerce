import { Model } from './model';
import { DeepPartial } from 'fishery';

export class Product extends Model {
	public readonly Name: string = '';

	public constructor( args?: DeepPartial< Product > ) {
		super();
		Object.assign( this, args );
	}
}
