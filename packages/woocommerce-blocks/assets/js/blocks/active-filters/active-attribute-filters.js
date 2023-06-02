/**
 * External dependencies
 */
import { useCollection, useQueryStateByKey } from '@woocommerce/base-hooks';
import { decodeEntities } from '@wordpress/html-entities';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { renderRemovableListItem } from './utils';
import { removeAttributeFilterBySlug } from '../../utils/attributes-query';

/**
 * Component that renders active attribute (terms) filters.
 *
 * @param {Object} props Incoming props for the component.
 * @param {Object} props.attributeObject The attribute object.
 * @param {Array} props.slugs The slugs for attributes.
 * @param {string} props.operator The operator for the filter.
 * @param {string} props.displayStyle The style used for displaying the filters.
 */
const ActiveAttributeFilters = ( {
	attributeObject = {},
	slugs = [],
	operator = 'in',
	displayStyle,
} ) => {
	const { results, isLoading } = useCollection( {
		namespace: '/wc/store',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeObject.id ],
	} );

	const [ productAttributes, setProductAttributes ] = useQueryStateByKey(
		'attributes',
		[]
	);

	if ( isLoading ) {
		return null;
	}

	const attributeLabel = attributeObject.label;

	return (
		<li>
			<span className="wc-block-active-filters__list-item-type">
				{ attributeLabel }:
			</span>
			<ul>
				{ slugs.map( ( slug, index ) => {
					const termObject = results.find( ( term ) => {
						return term.slug === slug;
					} );

					if ( ! termObject ) {
						return null;
					}

					let prefix = '';

					if ( index > 0 && operator === 'and' ) {
						prefix = (
							<span className="wc-block-active-filters__list-item-operator">
								{ __( 'and', 'woocommerce' ) }
							</span>
						);
					}

					return renderRemovableListItem( {
						type: attributeLabel,
						name: decodeEntities( termObject.name || slug ),
						prefix,
						removeCallback: () => {
							removeAttributeFilterBySlug(
								productAttributes,
								setProductAttributes,
								attributeObject,
								slug
							);
						},
						showLabel: false,
						displayStyle,
					} );
				} ) }
			</ul>
		</li>
	);
};

export default ActiveAttributeFilters;
