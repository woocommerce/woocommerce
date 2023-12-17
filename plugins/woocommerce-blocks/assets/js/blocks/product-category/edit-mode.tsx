/**
 * External dependencies
 */
import { Button, Placeholder } from '@wordpress/components';
import { Icon, file } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import ProductCategoryControl from '@woocommerce/editor-components/product-category-control';

/**
 * Internal dependencies
 */
import { Attributes, Props } from './types';

export interface EditModeProps extends Props {
	isEditing: boolean;
	setIsEditing: ( isEditing: boolean ) => void;
	changedAttributes: Partial< Attributes >;
	setChangedAttributes: ( changedAttributes: Partial< Attributes > ) => void;
}

export const ProductsByCategoryEditMode = (
	props: EditModeProps
): JSX.Element => {
	const {
		debouncedSpeak,
		setIsEditing,
		changedAttributes,
		setChangedAttributes,
		attributes,
	} = props;

	const currentAttributes = { ...attributes, ...changedAttributes };

	const stopEditing = () => {
		setIsEditing( false );
		setChangedAttributes( {} );
	};

	const save = () => {
		const { setAttributes } = props;

		setAttributes( changedAttributes );
		stopEditing();
	};

	const onDone = () => {
		save();
		debouncedSpeak(
			__(
				'Now displaying a preview of the reviews for the products in the selected categories.',
				'woocommerce'
			)
		);
	};

	const onCancel = () => {
		stopEditing();
		debouncedSpeak(
			__(
				'Now displaying a preview of the reviews for the products in the selected categories.',
				'woocommerce'
			)
		);
	};

	return (
		<Placeholder
			icon={ <Icon icon={ file } /> }
			label={ __( 'Products by Category', 'woocommerce' ) }
			className="wc-block-products-grid wc-block-products-category"
		>
			{ __(
				'Display a grid of products from your selected categories.',
				'woocommerce'
			) }
			<div className="wc-block-products-category__selection">
				<ProductCategoryControl
					selected={ currentAttributes.categories }
					onChange={ ( value = [] ) => {
						const ids = value.map( ( { id } ) => id );
						setChangedAttributes( { categories: ids } );
					} }
					operator={ currentAttributes.catOperator }
					onOperatorChange={ ( value = 'any' ) =>
						setChangedAttributes( { catOperator: value } )
					}
				/>
				<Button variant="primary" onClick={ onDone }>
					{ __( 'Done', 'woocommerce' ) }
				</Button>
				<Button
					className="wc-block-products-category__cancel-button"
					variant="tertiary"
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'woocommerce' ) }
				</Button>
			</div>
		</Placeholder>
	);
};
