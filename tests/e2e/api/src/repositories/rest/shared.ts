import { ModelTransformer } from '../../framework/model-transformer';
import { MetaData } from '../../models/shared-types';
import { IgnorePropertyTransformation } from '../../framework/transformations/ignore-property-transformation';

/**
 * Creates a new transformer for metadata models.
 *
 * @return {ModelTransformer} The created transformer.
 */
export function createMetaDataTransformer(): ModelTransformer< MetaData > {
	return new ModelTransformer(
		[
			new IgnorePropertyTransformation( [ 'id' ] ),
		],
	);
}
