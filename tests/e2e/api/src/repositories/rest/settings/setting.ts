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

function restList( httpClient: HTTPClient ): ListChildFn< SettingRepositoryParams > {
	return async ( parent ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent.settingGroupID );
		if ( response.statusCode >= 400 ) {
			throw response;
		}

		const list: Setting[] = [];
		for ( const raw of response.data ) {
			list.push( new Setting( {
				id: raw.id,
				label: raw.label,
				description: raw.description,
				type: raw.type,
				options: raw.options,
				default: raw.default,
				value: raw.value,
			} ) );
		}

		return Promise.resolve( list );
	};
}

function restRead( httpClient: HTTPClient ): ReadChildFn< SettingRepositoryParams > {
	return async ( parent, id ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent.settingGroupID + '/' + id );
		if ( response.statusCode >= 400 ) {
			throw response;
		}

		return Promise.resolve( new Setting( {
			id: response.data.id,
			label: response.data.label,
			description: response.data.description,
			type: response.data.type,
			options: response.data.options,
			default: response.data.default,
			value: response.data.value,
		} ) );
	};
}

function restUpdate( httpClient: HTTPClient ): UpdateChildFn< SettingRepositoryParams > {
	return async ( parent, id, params ) => {
		const response = await httpClient.patch(
			'/wc/v3/settings/' + parent.settingGroupID + '/' + id,
			params,
		);
		if ( response.statusCode >= 400 ) {
			throw response;
		}

		return Promise.resolve( new Setting( {
			id: response.data.id,
			label: response.data.label,
			description: response.data.description,
			type: response.data.type,
			options: response.data.options,
			default: response.data.default,
			value: response.data.value,
		} ) );
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
