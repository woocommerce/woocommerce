import { HTTPClient } from '../../../http';
import { ModelRepository, ParentID } from '../../../framework/model-repository';
import { Setting } from '../../../models';
import {
	ListsSettings,
	ReadsSettings,
	SettingRepositoryParams,
	UpdatesSettings,
} from '../../../models/settings/setting';
import { ModelTransformer } from '../../../framework/model-transformer';
import { restListChild, restReadChild, restUpdateChild } from '../shared';
import { ModelID } from '../../../models/model';

function createTransformer(): ModelTransformer< Setting > {
	return new ModelTransformer( [] );
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettings|ReadsSettings|UpdatesSettings} The created repository.
 */
export function settingRESTRepository( httpClient: HTTPClient ): ListsSettings & ReadsSettings & UpdatesSettings {
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
