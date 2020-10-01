import { HTTPClient } from '../../../http';
import { ListFn, ListsModels, ModelRepository, ModelRepositoryParams } from '../../../framework/model-repository';
import { SettingGroup } from '../../../models';

type SettingGroupParams = ModelRepositoryParams< SettingGroup, never, never, never >;

function restList( httpClient: HTTPClient ): ListFn< SettingGroupParams > {
	return async () => {
		const response = await httpClient.get( '/wc/v3/settings' );

		const list: SettingGroup[] = [];
		for ( const raw of response.data ) {
			list.push( new SettingGroup( {
				id: raw.id,
				label: raw.label,
				description: raw.description,
				parentID: raw.parent_id,
			} ) );
		}

		return Promise.resolve( list );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsModels.<SettingGroup>} A repository for interacting with models via the REST API.
 */
export function settingGroupRESTRepository( httpClient: HTTPClient ): ListsModels< SettingGroupParams > {
	return new ModelRepository< SettingGroupParams >(
		restList( httpClient ),
		null,
		null,
		null,
		null,
	);
}
