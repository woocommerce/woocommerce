/**
 * External dependencies
 */
import { useEffect } from 'react';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet, natively (not until 7.0.0).
// Including `@types/wordpress__data` as a devDependency causes build issues,
// so just going type-free for now.
// eslint-disable-next-line @woocommerce/dependency-group
import { useEntityRecords } from '@wordpress/core-data';
// eslint-disable-next-line @typescript-eslint/ban-ts-comment
// @ts-ignore No types for this exist yet, natively (not until 7.0.0).
// Including `@types/wordpress__data` as a devDependency causes build issues,
// so just going type-free for now.
// eslint-disable-next-line @woocommerce/dependency-group
import { useDispatch, useSelect, select as WPSelect } from '@wordpress/data';

export const useLayoutTemplates = () => {
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

	const { records: layoutTemplates, isResolving } = useEntityRecords(
		'root',
		'wcLayoutTemplate'
	);

	return [ layoutTemplates, isResolving ];
};
