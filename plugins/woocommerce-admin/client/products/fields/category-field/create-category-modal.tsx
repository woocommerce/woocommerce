/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import interpolateComponents from '@automattic/interpolate-components';
import { Button, Modal, Spinner, TextControl } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	useAsyncFilter,
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlMenu as Menu,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME,
	ProductCategory,
} from '@woocommerce/data';

/**
 * Internal dependencies
 */
import './create-category-modal.scss';
import { useCategorySearch } from './use-category-search';
import { CategoryFieldItem } from './category-field-item';

type CategoryItem = Pick< ProductCategory, 'id' | 'name' >;

type CreateCategoryModalProps = {
	initialCategoryName?: string;
	onCancel: () => void;
	onCreate: ( newCategory: ProductCategory ) => void;
};

export const CreateCategoryModal: React.FC< CreateCategoryModalProps > = ( {
	initialCategoryName,
	onCancel,
	onCreate,
} ) => {
	const {
		categoriesSelectList,
		isSearching,
		categoryTreeKeyValues,
		searchCategories,
	} = useCategorySearch();
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isCreating, setIsCreating ] = useState( false );
	const { createProductCategory, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME );
	const [ categoryName, setCategoryName ] = useState(
		initialCategoryName || ''
	);
	const [ categoryParent, setCategoryParent ] =
		useState< CategoryItem | null >( null );

	const onSave = async () => {
		recordEvent( 'product_category_add', {
			new_product_page: true,
		} );
		setIsCreating( true );
		try {
			const newCategory: ProductCategory = await createProductCategory( {
				name: categoryName,
				parent: categoryParent ? categoryParent.id : undefined,
			} );
			invalidateResolutionForStoreSelector( 'getProductCategories' );
			setIsCreating( false );
			onCreate( newCategory );
		} catch ( e ) {
			createNotice(
				'error',
				__( 'Failed to create category.', 'woocommerce' )
			);
			setIsCreating( false );
			onCancel();
		}
	};

	const selectProps = {
		items: categoriesSelectList,
		label: interpolateComponents( {
			mixedString: __( 'Parent category {{optional/}}', 'woocommerce' ),
			components: {
				optional: (
					<span className="woocommerce-product-form__optional-input">
						{ __( '(optional)', 'woocommerce' ) }
					</span>
				),
			},
		} ),
		selected: categoryParent,
		onSelect: ( item: CategoryItem ) => item && setCategoryParent( item ),
		onRemove: () => setCategoryParent( null ),
		getItemLabel: ( item: CategoryItem | null ) => item?.name || '',
		getItemValue: ( item: CategoryItem | null ) => item?.id || '',
	};

	const selectPropsWithAsycFilter = useAsyncFilter< CategoryItem >( {
		...selectProps,
		filter: searchCategories,
	} );

	return (
		<Modal
			title={ __( 'Create category', 'woocommerce' ) }
			onRequestClose={ () => onCancel() }
			className="woocommerce-create-new-category-modal"
		>
			<div className="woocommerce-create-new-category-modal__wrapper">
				<TextControl
					label={ __( 'Name', 'woocommerce' ) }
					name="Tops"
					value={ categoryName }
					onChange={ setCategoryName }
				/>
				<SelectControl< CategoryItem > { ...selectPropsWithAsycFilter }>
					{ ( {
						items,
						isOpen,
						getMenuProps,
						highlightedIndex,
						getItemProps,
					} ) => {
						return (
							<Menu
								isOpen={ isOpen }
								getMenuProps={ getMenuProps }
								className="woocommerce-category-field-dropdown__menu"
							>
								{ [
									isSearching ? (
										<div
											key="loading-spinner"
											className="woocommerce-category-field-dropdown__item"
										>
											<div className="woocommerce-category-field-dropdown__item-content">
												<Spinner />
											</div>
										</div>
									) : null,
									...items
										.filter(
											( item ) =>
												categoryTreeKeyValues[ item.id ]
													?.parentID === 0
										)
										.map( ( item ) => {
											return (
												<CategoryFieldItem
													key={ `${ item.id }` }
													item={
														categoryTreeKeyValues[
															item.id
														]
													}
													selectedIds={
														categoryParent
															? [
																	categoryParent.id,
															  ]
															: []
													}
													items={ items }
													highlightedIndex={
														highlightedIndex
													}
													getItemProps={
														getItemProps
													}
												/>
											);
										} ),
								].filter(
									( item ): item is JSX.Element =>
										item !== null
								) }
							</Menu>
						);
					} }
				</SelectControl>
				<div className="woocommerce-create-new-category-modal__buttons">
					<Button
						isSecondary
						onClick={ () => onCancel() }
						disabled={ isCreating }
					>
						{ __( 'Cancel', 'woocommerce' ) }
					</Button>
					<Button
						isPrimary
						disabled={ categoryName.length === 0 || isCreating }
						isBusy={ isCreating }
						onClick={ () => {
							onSave();
						} }
					>
						{ __( 'Save', 'woocommerce' ) }
					</Button>
				</div>
			</div>
		</Modal>
	);
};
