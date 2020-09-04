import { Model } from '../framework/model';
import { DeepPartial } from 'fishery';

/**
 * The base class for all product types.
 */
export abstract class Product extends Model {
	public readonly name: string = '';
	public readonly regularPrice: string = '';

	protected constructor( partial: DeepPartial< Product > = {} ) {
		super( partial );
		Object.assign( this, partial );
	}
}
