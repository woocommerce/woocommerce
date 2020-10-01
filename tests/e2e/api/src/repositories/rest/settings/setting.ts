import { HTTPClient } from '../../../http';
import {
	ListChildFn,
	ListsChildModels,
	ModelRepository,
	ModelRepositoryParams,
	ReadChildFn,
	ReadsChildModels,
	UpdateChildFn,
	UpdatesChildModels,
} from '../../../framework/model-repository';
import { Setting } from '../../../models';

/**
 * @typedef SettingParentID
 * @property {string} settingGroupID The ID of the setting group we're a child of.
 */
type SettingParentID = { settingGroupID: string };

type SettingParams = ModelRepositoryParams< Setting, SettingParentID, never, 'value' >;

function restList( httpClient: HTTPClient ): ListChildFn< SettingParams > {
	return async ( parent ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent.settingGroupID );

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

function restRead( httpClient: HTTPClient ): ReadChildFn< SettingParams > {
	return async ( parent, id ) => {
		const response = await httpClient.get( '/wc/v3/settings/' + parent.settingGroupID + '/' + id );

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

function restUpdate( httpClient: HTTPClient ): UpdateChildFn< SettingParams > {
	return async ( parent, id, params ) => {
		const response = await httpClient.patch(
			'/wc/v3/settings/' + parent.settingGroupID + '/' + id,
			params,
		);

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
 * @return {
 *	ListsChildModels.<Setting,SettingParentID>|
 *	ReadsChildModels.<Setting,SettingParentID>|
 *	UpdatesChildModels.<Setting,SettingParentID>
 * } A repository for interacting with models via the REST API.
 */
export function settingRESTRepository( httpClient: HTTPClient ):
	ListsChildModels< SettingParams > &
	ReadsChildModels< SettingParams > &
	UpdatesChildModels< SettingParams > {
	return new ModelRepository< SettingParams >(
		restList( httpClient ),
		null,
		restRead( httpClient ),
		restUpdate( httpClient ),
		null,
	);
}
