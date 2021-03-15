import { HTTPClient } from '../../../http';
import {
	ModelRepository,
	ModelTransformer,
	KeyChangeTransformation,
} from '../../../framework';
import {
	SettingGroup,
	ListsSettingGroups,
	SettingGroupRepositoryParams,
} from '../../../models';
import { restList } from '../shared';

function createTransformer(): ModelTransformer< SettingGroup > {
	return new ModelTransformer(
		[
			new KeyChangeTransformation< SettingGroup >( { parentID: 'parent_id' } ),
		],
	);
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettingGroups} The created repository.
 */
export default function settingGroupRESTRepository( httpClient: HTTPClient ): ListsSettingGroups {
	const transformer = createTransformer();

	return new ModelRepository(
		restList< SettingGroupRepositoryParams >( () => '/wc/v3/settings', SettingGroup, httpClient, transformer ),
		null,
		null,
		null,
		null,
	);
}
