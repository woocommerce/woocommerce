/**
 * External dependencies
 */
import { useEffect } from 'react';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet, natively (not until 7.0.0).
// Including `@types/wordpress__data` as a devDependency causes build issues,
// so just going type-free for now.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityRecord } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet, natively (not until 7.0.0).
// Including `@types/wordpress__data` as a devDependency causes build issues,
// so just going type-free for now.
// eslint-disable-next-line @woocommerce/dependency-group
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';

export const useLayoutTemplate = ( layoutTemplateId: string | undefined ) => {
	const layoutTemplateEntity = useSelect( ( select: typeof WPSelect ) => {
		const { getEntityConfig } = select( 'core' );
		return getEntityConfig( 'root', 'wcLayoutTemplate' );
	} );

	const { addEntities } = useDispatch( 'core' );

	useEffect( () => {
		if ( ! layoutTemplateEntity ) {
			addEntities( [
				{
					kind: 'root',
					name: 'wcLayoutTemplate',
					baseURL: '/wc/v3/layout-templates',
					label: 'Layout Templates',
				},
			] );
		}
	}, [ addEntities, layoutTemplateEntity ] );
	console.log( layoutTemplateId );

	const { record: layoutTemplate, isResolving } = useEntityRecord(
		'root',
		'wcLayoutTemplate',
		// Because of the regression mentioned below, REST API requests will still be triggered
		// even when the query is disabled. This means that if we pass `undefined`/`null` as the ID,
		// the query will be triggered with no ID, which will return all layout templates.
		// To prevent this, we pass `__invalid-template-id` as the ID when there is no layout template ID.
		// A request will still be triggered, but it will return no results.
		layoutTemplateId || '__invalid-template-id'
		// Only perform the query if we have a layout template ID; otherwise, just return null.
		// Note: Until we are using @woocommerce/core-data 6.24.0 (Gutenberg 17.2),
		// the REST API requests will still be triggered even when the query is disabled due to a regression.
		// See: https://github.com/WordPress/gutenberg/pull/56108
		{ enabled: !! layoutTemplateId }
	);

	return { layoutTemplate, isResolving };
};
