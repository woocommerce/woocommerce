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
		const { getEntity } = select( 'core' );
		return getEntity( 'root', 'wcLayoutTemplate' );
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

	const { record: layoutTemplate, isResolving } = useEntityRecord(
		'root',
		'wcLayoutTemplate',
		layoutTemplateId
	);

	return { layoutTemplate, isResolving };
};
