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

const connectBlockWithEntityProp =
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

				const { attributes, setAttributes } = props;
				const { content } = attributes;

				const [ propertyContent, setPropertyContent ] = useEntityProp(
					'postType',
					'product',
					entityProp
				);

				/*
				 * Populate initial content
				 * from the product entity to the block
				 */
				useEffect( () => {
					if ( ! propertyContent ) {
						return;
					}

					setAttributes( { content: propertyContent } );
				}, [ setAttributes ] ); // eslint-disable-line

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
		'connectBlockWithEntityProp'
	);

export default connectBlockWithEntityProp;
