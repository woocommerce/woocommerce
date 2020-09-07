import { DeepPartial } from 'fishery';
import { Product } from './product';

/**
 * The simple product class.
 */
export class SimpleProduct extends Product {
	public constructor( partial: DeepPartial< SimpleProduct > = {} ) {
		super( partial );
		Object.assign( this, partial );
	}
}
