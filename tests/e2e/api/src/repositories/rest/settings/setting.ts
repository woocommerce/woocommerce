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

function fromServer( data: any ): Setting {
	if ( ! data.id ) {
		throw new Error( 'An invalid response was received.' );
	}

	const t = new ModelTransformer< Setting >( [] );
	return t.toModel( Setting, data );
}

function restList( httpClient: HTTPClient ): ListChildFn< SettingRepositoryParams > {
	return async ( parent ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent );

		const list: Setting[] = [];
		for ( const raw of response.data ) {
			list.push( fromServer( raw ) );
		}

		return Promise.resolve( list );
	};
}

function restRead( httpClient: HTTPClient ): ReadChildFn< SettingRepositoryParams > {
	return async ( parent, id ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent + '/' + id );
		return Promise.resolve( fromServer( response.data ) );
	};
}

function restUpdate( httpClient: HTTPClient ): UpdateChildFn< SettingRepositoryParams > {
	return async ( parent, id, params ) => {
		const response = await httpClient.patch(
			'/wc/v3/settings/' + parent + '/' + id,
			params,
		);

		return Promise.resolve( fromServer( response.data ) );
	};
}

/**
 * Creates a new ModelRepository instance for interacting with models via the REST API.
 *
 * @param {HTTPClient} httpClient The HTTP client for the REST requests to be made using.
 * @return {ListsSettings|ReadsSettings|UpdatesSettings} The created repository.
 */
export function settingRESTRepository( httpClient: HTTPClient ): ListsSettings & ReadsSettings & UpdatesSettings {
	return new ModelRepository(
		restList( httpClient ),
		null,
		restRead( httpClient ),
		restUpdate( httpClient ),
		null,
	);
}
