import { HTTPClient } from '../../../http';
import { ListFn, ModelRepository } from '../../../framework/model-repository';
import { SettingGroup } from '../../../models';
import { ListsSettingGroups, SettingGroupRepositoryParams } from '../../../models/settings/setting-group';
import { ModelTransformer } from '../../../framework/model-transformer';
import { KeyChangeTransformation } from '../../../framework/transformations/key-change-transformation';

function createTransformer(): ModelTransformer< SettingGroup > {
	return new ModelTransformer(
		[
			new KeyChangeTransformation< SettingGroup >( { parentID: 'parent_id' } ),
		],
	);
}

function restList(
	httpClient: HTTPClient,
	transformer: ModelTransformer< SettingGroup >,
): ListFn< SettingGroupRepositoryParams > {
	return async () => {
		const response = await httpClient.get( '/wc/v3/settings' );

		const list: SettingGroup[] = [];
		for ( const raw of response.data ) {
			list.push( transformer.toModel( SettingGroup, raw ) );
		}

		return Promise.resolve( list );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettingGroups} The created repository.
 */
export function settingGroupRESTRepository( httpClient: HTTPClient ): ListsSettingGroups {
	const transformer = createTransformer();

	return new ModelRepository(
		restList( httpClient, transformer ),
		null,
		null,
		null,
		null,
	);
}
