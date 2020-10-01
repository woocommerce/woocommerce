import { HTTPClient } from '../../../http';
import { ListFn, ModelRepository } from '../../../framework/model-repository';
import { SettingGroup } from '../../../models';
import { ListsSettingGroups, SettingGroupRepositoryParams } from '../../../models/settings/setting-group';

function restList( httpClient: HTTPClient ): ListFn< SettingGroupRepositoryParams > {
	return async () => {
		const response = await httpClient.get( '/wc/v3/settings' );
		if ( response.statusCode >= 400 ) {
			throw response;
		}

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
 * @return {ListsSettingGroups} The created repository.
 */
export function settingGroupRESTRepository( httpClient: HTTPClient ): ListsSettingGroups {
	return new ModelRepository(
		restList( httpClient ),
		null,
		null,
		null,
		null,
	);
}
