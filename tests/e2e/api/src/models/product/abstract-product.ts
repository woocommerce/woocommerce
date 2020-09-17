import { Model } from '../model';

/**
 * The base class for all product types.
 */
export abstract class AbstractProduct extends Model {
	public readonly name: string = '';
	public readonly regularPrice: string = '';
}
