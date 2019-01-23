/** @format */
/**
 * External dependencies
 */
import { Component } from '@wordpress/element';
import { first, last } from 'lodash';
import { Spinner } from '@wordpress/components';

/**
 * WooCommerce dependencies
 */
import { Link } from '@woocommerce/components';
import { getNewPath, getPersistedQuery } from '@woocommerce/navigation';

export default class CategoryBreadcrumbs extends Component {
	getCategoryAncestorIds( category, categories ) {
		const ancestors = [];
		let parent = category.parent;
		while ( parent ) {
			ancestors.unshift( parent );
			parent = categories[ parent ].parent;
		}
		return ancestors;
	}

	getCategoryAncestors( category, categories ) {
		const ancestorIds = this.getCategoryAncestorIds( category, categories );

		if ( ! ancestorIds.length ) {
			return;
		}
		if ( ancestorIds.length === 1 ) {
			return categories[ first( ancestorIds ) ].name + ' › ';
		}
		if ( ancestorIds.length === 2 ) {
			return (
				categories[ first( ancestorIds ) ].name +
				' › ' +
				categories[ last( ancestorIds ) ].name +
				' › '
			);
		}
		return (
			categories[ first( ancestorIds ) ].name +
			' … ' +
			categories[ last( ancestorIds ) ].name +
			' › '
		);
	}

	render() {
		const { categories, category, query } = this.props;
		const persistedQuery = getPersistedQuery( query );

		return category ? (
			<div className="woocommerce-table__breadcrumbs">
				{ this.getCategoryAncestors( category, categories ) }
				<Link
					href={ getNewPath( persistedQuery, 'categories', {
						filter: 'single_category',
						categories: category.id,
					} ) }
					type="wc-admin"
				>
					{ category.name }
				</Link>
			</div>
		) : (
			<Spinner />
		);
	}
}
