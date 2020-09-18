import { AsyncFactory } from '../framework/async-factory';
import { commerce } from 'faker/locale/en';
import { SimpleProduct } from '../models';
import { CreateFn } from '../framework/model-repository';

/**
 * Creates a new factory for creating models.
 *
 * @param {Function} creator The function we will use for creating models.
 * @return {AsyncFactory} The factory for creating models.
 */
export function simpleProductFactory( creator: CreateFn< SimpleProduct > ): AsyncFactory< SimpleProduct > {
	return new AsyncFactory< SimpleProduct >(
		( { params } ) => {
			return new SimpleProduct( {
				name: params.name ?? commerce.productName(),
				regularPrice: params.regularPrice ?? commerce.price(),
			} );
		},
		creator,
	);
}
