/**
 * External dependencies
 */
import { Placeholder, Icon, Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';
import ProductControl from '@woocommerce/editor-components/product-control';

/**
 * Internal dependencies
 */
import { getClassPrefixFromName } from './utils';
import { BLOCK_NAMES } from './constants';

export const withEditMode = ( { description, editLabel, icon, label } ) => (
	Component
) => ( props ) => {
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
							selected={ [ attributes.categoryId ] }
							onChange={ ( value = [] ) => {
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
							selected={ attributes.productId || 0 }
							showVariations
							onChange={ ( value = [] ) => {
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
					<Button isPrimary onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	}

	return <Component { ...props } />;
};
