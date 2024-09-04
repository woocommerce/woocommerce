/**
 * External dependencies
 */
import { createElement, Fragment } from '@wordpress/element';
import { privateApis as routerPrivateApis } from '@wordpress/router';
// @ts-expect-error missing type.
// eslint-disable-next-line @wordpress/no-unsafe-wp-apis, @woocommerce/dependency-group
import { __experimentalItemGroup as ItemGroup } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { unlock } from '../../lock-unlock';
import DataViewItem from './dataview-item';
import { useDefaultViews } from './default-views';

const { useLocation } = unlock( routerPrivateApis );

export default function DataViewsSidebarContent() {
	const {
		params: {
			postType = 'product',
			activeView = 'all',
			isCustom = 'false',
		},
	} = useLocation();
	const defaultViews = useDefaultViews( { postType } );
	if ( ! postType ) {
		return null;
	}
	const isCustomBoolean = isCustom === 'true';

	return (
		<>
			<ItemGroup>
				{ defaultViews.map( ( dataview ) => {
					return (
						<DataViewItem
							key={ dataview.slug }
							slug={ dataview.slug }
							title={ dataview.title }
							icon={ dataview.icon }
							type={ dataview.view.type }
							isActive={
								! isCustomBoolean &&
								dataview.slug === activeView
							}
							isCustom={ false }
						/>
					);
				} ) }
			</ItemGroup>
		</>
	);
}
