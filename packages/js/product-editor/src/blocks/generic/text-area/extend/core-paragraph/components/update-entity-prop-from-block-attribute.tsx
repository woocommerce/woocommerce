/**
 * External dependencies
 */
import { createHigherOrderComponent } from '@wordpress/compose';
import { useEntityProp } from '@wordpress/core-data';
import { createElement, useEffect } from '@wordpress/element';
/**
 * Internal dependencies
 */
import type {
	ConnectedBlockEditComponent,
	ConnectedBlockEditInstance,
} from '../../../types';

/**
 * Higher order component to connect the block with the product entity property.
 * This component is responsible ONLY for updating the product entity property.
 * This is a temporary solution until the Block Binding API is 100% available.
 */
const updateEntityPropFromBlockAttribute =
	createHigherOrderComponent< ConnectedBlockEditComponent >(
		( BlockEdit: ConnectedBlockEditComponent ) => {
			return ( props: ConnectedBlockEditInstance ) => {
				/*
				 * Check whether the property `product-editor/entity-prop`
				 * is set in the block context.
				 */
				const { context } = props;
				const entityProp = context?.[ 'product-editor/entity-prop' ];
				if ( ! entityProp ) {
					return <BlockEdit { ...props } />;
				}

				const { attributes } = props;
				const { content } = attributes;

				const [ , setPropertyContent ] = useEntityProp(
					'postType',
					'product',
					entityProp
				);

				/*
				 * Update the product entity
				 * with the block content
				 */
				useEffect( () => {
					if ( ! content ) {
						return;
					}

					setPropertyContent( content );
				}, [ content, setPropertyContent ] );

				return <BlockEdit { ...props } />;
			};
		},
		'updateEntityPropFromBlockAttribute'
	);

export default updateEntityPropFromBlockAttribute;
