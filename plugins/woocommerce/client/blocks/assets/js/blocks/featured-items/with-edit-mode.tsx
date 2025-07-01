/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { ComponentType } from 'react';
import { useEffect } from '@wordpress/element';
import { info } from '@wordpress/icons';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductControl from '@woocommerce/editor-components/product-control';
import {
	ProductResponseItem,
	ProductCategoryResponseItem,
} from '@woocommerce/types';
import {
	Placeholder,
	Icon,
	Button,
	// @ts-expect-error Using experimental features
	__experimentalHStack as HStack,
	// @ts-expect-error Using experimental features
	__experimentalText as Text,
} from '@wordpress/components';

/**
 * Internal dependencies
 */

import { BLOCK_NAMES } from './constants';
import { EditorBlock, GenericBlockUIConfig } from './types';
import { getClassPrefixFromName, getInvalidItemDescription } from './utils';
import { useFeaturedItemStatus } from './use-featured-item-status';

interface EditModeConfiguration extends GenericBlockUIConfig {
	description: string;
	editLabel: string;
}

type EditModeRequiredAttributes = {
	categoryId?: number;
	editMode: boolean;
	mediaId: number;
	mediaSrc: string;
	productId?: number;
};

interface EditModeRequiredProps< T > {
	attributes: EditModeRequiredAttributes & EditorBlock< T >[ 'attributes' ];
	debouncedSpeak: ( label: string ) => void;
	setAttributes: ( attrs: Partial< EditModeRequiredAttributes > ) => void;
	triggerUrlUpdate: () => void;
	isLoading: boolean;
}

type EditModeProps< T extends EditorBlock< T > > = T &
	EditModeRequiredProps< T >;

export const withEditMode =
	( { description, editLabel, icon, label }: EditModeConfiguration ) =>
	< T extends EditorBlock< T > >( Component: ComponentType< T > ) =>
	( props: EditModeProps< T > ) => {
		const {
			attributes,
			debouncedSpeak,
			name,
			setAttributes,
			triggerUrlUpdate = () => void null,
		} = props;

		const className = getClassPrefixFromName( name );

		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak( editLabel );
		};

		const itemId =
			name === BLOCK_NAMES.featuredProduct
				? attributes?.productId
				: attributes?.categoryId;

		const { status, isDeleted, isLoading } = useFeaturedItemStatus( {
			itemId,
			itemType: name,
		} );

		useEffect( () => {
			const currEditModeValue =
				( name === BLOCK_NAMES.featuredProduct &&
					status !== 'publish' ) ||
				isDeleted;

			if (
				currEditModeValue !== attributes.editMode &&
				typeof currEditModeValue === 'boolean'
			) {
				setAttributes( { editMode: currEditModeValue } );
			}
		}, [ status, isDeleted, attributes.editMode, name, setAttributes ] );

		if ( isLoading ) {
			return (
				<Placeholder
					icon={ <Icon icon={ icon } /> }
					label={ label }
					className={ className }
				>
					<div>{ __( 'Loading…', 'woocommerce' ) }</div>
				</Placeholder>
			);
		}

		if ( attributes.editMode ) {
			return (
				<Placeholder
					icon={ <Icon icon={ icon } /> }
					label={ label }
					className={ className }
				>
					<HStack alignment="center">
						{ attributes.productId || attributes.categoryId ? (
							<Icon
								icon={ info }
								className="wc-blocks-featured-items__orange-info-icon"
							/>
						) : (
							<Icon icon={ info } />
						) }
						<Text>
							{ attributes.productId || attributes.categoryId
								? getInvalidItemDescription( name )
								: description }
						</Text>
					</HStack>
					<div className={ `${ className }__selection` }>
						{ name === BLOCK_NAMES.featuredCategory && (
							<ProductCategoryControl
								selected={
									attributes.categoryId
										? [ attributes.categoryId ]
										: []
								}
								onChange={ (
									value: ProductCategoryResponseItem[] = []
								) => {
									const id = value[ 0 ] ? value[ 0 ].id : 0;
									setAttributes( {
										categoryId: id,
										mediaId: 0,
										mediaSrc: '',
									} );
									triggerUrlUpdate();
								} }
								isSingle
							/>
						) }
						{ name === BLOCK_NAMES.featuredProduct && (
							<ProductControl
								selected={
									attributes.productId
										? [ attributes.productId ]
										: []
								}
								showVariations
								onChange={ (
									value: ProductResponseItem[] = []
								) => {
									const id = value[ 0 ] ? value[ 0 ].id : 0;
									setAttributes( {
										productId: id,
										mediaId: 0,
										mediaSrc: '',
									} );
									triggerUrlUpdate();
								} }
							/>
						) }
						<Button variant="primary" onClick={ onDone }>
							{ __( 'Done', 'woocommerce' ) }
						</Button>
					</div>
				</Placeholder>
			);
		}

		return <Component { ...props } />;
	};
