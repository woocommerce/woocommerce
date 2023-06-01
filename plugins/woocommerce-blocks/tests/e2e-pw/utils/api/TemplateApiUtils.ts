/**
 * External dependencies
 */
import { request as req } from '@playwright/test';
import fs from 'fs/promises';
/**
 * Internal dependencies
 */
import { BASE_URL, STORAGE_STATE_PATH } from '../constants';

export class TemplateApiUtils {
	request: typeof req;
	constructor( request: typeof req ) {
		this.request = request;
	}
	async revertTemplate( slug: string ) {
		const storageState = JSON.parse(
			await fs.readFile( STORAGE_STATE_PATH, 'utf-8' )
		);
		const requestUtils = await this.request.newContext( {
			baseURL: BASE_URL,
			storageState: storageState && {
				cookies: storageState.cookies,
				origins: [],
			},
		} );

		const response = await requestUtils.get(
			`/wp-json/wp/v2/templates/${ slug }?context=edit&source=theme&_locale=user`,
			{
				headers: {
					'X-WP-Nonce': storageState.nonce,
				},
			}
		);

		const { content } = await response.json();

		await requestUtils.post(
			`wp-json/wp/v2/templates/${ slug }?_locale=user`,
			{
				data: {
					id: slug,
					content: content.raw,
					source: 'theme',
				},
				headers: {
					'X-WP-Nonce': storageState.nonce,
				},
			}
		);
	}

	async getTemplates() {
		const storageState = JSON.parse(
			await fs.readFile( STORAGE_STATE_PATH, 'utf-8' )
		);

		const requestUtils = await this.request.newContext( {
			baseURL: BASE_URL,
			storageState: storageState && {
				cookies: storageState.cookies,
				origins: [],
			},
		} );
		const response = await requestUtils.get( `/wp-json/wp/v2/templates`, {
			headers: {
				'X-WP-Nonce': storageState.nonce,
			},
		} );

		return await response.json();
	}
}
