/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import type { ComponentType } from 'react';
import { useEffect, useState } from '@wordpress/element';
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
	__experimentalHStack as HStack,
	__experimentalText as Text,
} from '@wordpress/components';
import { ErrorObject } from '@woocommerce/base-utils';

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
	mediaId: number;
	mediaSrc: string;
	productId?: number;
};

interface EditModeRequiredProps< T > {
	attributes: EditModeRequiredAttributes & EditorBlock< T >[ 'attributes' ];
	clientId: string;
	debouncedSpeak: ( label: string ) => void;
	setAttributes: ( attrs: Partial< EditModeRequiredAttributes > ) => void;
	triggerUrlUpdate: () => void;
	isLoading: boolean;
	error?: ErrorObject | null;
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
			error,
		} = props;

		const className = getClassPrefixFromName( name );
		const [ selectedOptions, setSelectedOptions ] = useState< {
			productId?: number;
			categoryId?: number;
			mediaId: number;
			mediaSrc: string;
		} >();

		const hasFeaturedItemId =
			( name === BLOCK_NAMES.featuredProduct && attributes.productId ) ||
			( name === BLOCK_NAMES.featuredCategory && attributes.categoryId );

		// Only show edit mode for newly inserted blocks without existing selection
		const [ editMode, setEditMode ] = useState< boolean >(
			! hasFeaturedItemId
		);

		const onDone = () => {
			if ( selectedOptions ) {
				setAttributes( selectedOptions );
				setEditMode( false );
				debouncedSpeak( editLabel );
			}
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
			if ( ! isLoading ) {
				const currEditModeValue =
					( name === BLOCK_NAMES.featuredProduct &&
						status !== 'publish' ) ||
					isDeleted;

				if ( currEditModeValue ) {
					setEditMode( currEditModeValue );
				}
			}
		}, [ status, isDeleted, name, isLoading ] );

		if ( editMode ) {
			return (
				<Placeholder
					icon={ <Icon icon={ icon } /> }
					label={ label }
					className={ className }
				>
					<HStack alignment="center">
						{ isDeleted ? (
							<Icon
								icon={ info }
								className="wc-blocks-featured-items__orange-info-icon"
							/>
						) : (
							<Icon icon={ info } />
						) }
						<Text>
							{ isDeleted
								? getInvalidItemDescription( name )
								: description }
						</Text>
					</HStack>
					<div className={ `${ className }__selection` }>
						{ name === BLOCK_NAMES.featuredCategory && (
							<ProductCategoryControl
								selected={
									selectedOptions?.categoryId
										? [ selectedOptions.categoryId ]
										: []
								}
								onChange={ (
									value: ProductCategoryResponseItem[] = []
								) => {
									const id = value[ 0 ] ? value[ 0 ].id : 0;
									setSelectedOptions( {
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
									selectedOptions?.productId
										? [ selectedOptions.productId ]
										: []
								}
								showVariations
								onChange={ (
									value: ProductResponseItem[] = []
								) => {
									const id = value[ 0 ] ? value[ 0 ].id : 0;
									setSelectedOptions( {
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

		return (
			<Component
				{ ...props }
				isLoading={ isLoading }
				error={ isLoading ? null : error }
				useEditMode={ [ editMode, setEditMode ] }
			/>
		);
	};
