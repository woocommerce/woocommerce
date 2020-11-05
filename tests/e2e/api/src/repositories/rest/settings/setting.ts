import { HTTPClient } from '../../../http';
import {
	ListChildFn,
	ModelRepository,
	ReadChildFn,
	UpdateChildFn,
} from '../../../framework/model-repository';
import { Setting } from '../../../models';
import {
	ListsSettings,
	ReadsSettings,
	SettingRepositoryParams,
	UpdatesSettings,
} from '../../../models/settings/setting';
import { ModelTransformer } from '../../../framework/model-transformer';

function createTransformer(): ModelTransformer< Setting > {
	return new ModelTransformer( [] );
}

function restList(
	httpClient: HTTPClient,
	transformer: ModelTransformer< Setting >,
): ListChildFn< SettingRepositoryParams > {
	return async ( parent ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent );

		const list: Setting[] = [];
		for ( const raw of response.data ) {
			list.push( transformer.toModel( Setting, raw ) );
		}

		return Promise.resolve( list );
	};
}

function restRead(
	httpClient: HTTPClient,
	transformer: ModelTransformer< Setting >,
): ReadChildFn< SettingRepositoryParams > {
	return async ( parent, id ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent + '/' + id );
		return Promise.resolve( transformer.toModel( Setting, response.data ) );
	};
}

function restUpdate(
	httpClient: HTTPClient,
	transformer: ModelTransformer< Setting >,
): UpdateChildFn< SettingRepositoryParams > {
	return async ( parent, id, params ) => {
		const response = await httpClient.patch(
			'/wc/v3/settings/' + parent + '/' + id,
			transformer.fromModel( params ),
		);

		return Promise.resolve( transformer.toModel( Setting, response.data ) );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettings|ReadsSettings|UpdatesSettings} The created repository.
 */
export function settingRESTRepository( httpClient: HTTPClient ): ListsSettings & ReadsSettings & UpdatesSettings {
	const transformer = createTransformer();

	return new ModelRepository(
		restList( httpClient, transformer ),
		null,
		restRead( httpClient, transformer ),
		restUpdate( httpClient, transformer ),
		null,
	);
}
