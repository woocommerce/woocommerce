/**
 * External dependencies
 */

import {
	ProductResponseItem,
	ProductCategoryResponseItem,
} from '@woocommerce/types';
import { Placeholder, Icon, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductControl from '@woocommerce/editor-components/product-control';
import type { ComponentType } from 'react';

/**
 * Internal dependencies
 */
import { BLOCK_NAMES } from './constants';
import { EditorBlock, GenericBlockUIConfig } from './types';
import { getClassPrefixFromName } from './utils';

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

		if ( attributes.editMode ) {
			return (
				<Placeholder
					icon={ <Icon icon={ icon } /> }
					label={ label }
					className={ className }
				>
					{ description }
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
							{ __( 'Done', 'woo-gutenberg-products-block' ) }
						</Button>
					</div>
				</Placeholder>
			);
		}

		return <Component { ...props } />;
	};
