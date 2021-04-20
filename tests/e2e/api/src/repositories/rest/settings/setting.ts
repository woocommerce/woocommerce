import { HTTPClient } from '../../../http';
import {
	ModelRepository,
	ParentID,
	ModelTransformer,
} from '../../../framework';
import {
	ModelID,
	Setting,
	ListsSettings,
	ReadsSettings,
	SettingRepositoryParams,
	UpdatesSettings,
} from '../../../models';
import { restListChild, restReadChild, restUpdateChild } from '../shared';

function createTransformer(): ModelTransformer< Setting > {
	return new ModelTransformer( [] );
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettings|ReadsSettings|UpdatesSettings} The created repository.
 */
export default function settingRESTRepository( httpClient: HTTPClient ): ListsSettings & ReadsSettings & UpdatesSettings {
	const buildURL = ( parent: ParentID< SettingRepositoryParams >, id: ModelID ) => '/wc/v3/settings/' + parent + '/' + id;
	const transformer = createTransformer();

	return new ModelRepository(
		restListChild< SettingRepositoryParams >( ( parent ) => '/wc/v3/settings/' + parent, Setting, httpClient, transformer ),
		null,
		restReadChild< SettingRepositoryParams >( buildURL, Setting, httpClient, transformer ),
		restUpdateChild< SettingRepositoryParams >( buildURL, Setting, httpClient, transformer ),
		null,
	);
}
