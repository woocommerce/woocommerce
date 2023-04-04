/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, Spinner, TextControl } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import {
	useState,
	createElement,
	createInterpolateElement,
} from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
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
import { ProductCategoryNode, useCategorySearch } from './use-category-search';
import { CategoryFieldItem } from './category-field-item';

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
		getFilteredItems,
	} = useCategorySearch();
	const { createNotice } = useDispatch( 'core/notices' );
	const [ isCreating, setIsCreating ] = useState( false );
	const { createProductCategory, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME );
	const [ categoryName, setCategoryName ] = useState(
		initialCategoryName || ''
	);
	const [ categoryParent, setCategoryParent ] =
		useState< ProductCategoryNode | null >( null );

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

	const debouncedSearch = useDebounce( searchCategories, 250 );

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
				<SelectControl< ProductCategoryNode >
					items={ categoriesSelectList }
					label={ createInterpolateElement(
						__( 'Parent category <optional/>', 'woocommerce' ),
						{
							optional: (
								<span className="woocommerce-product-form__optional-input">
									{ __( '(optional)', 'woocommerce' ) }
								</span>
							),
						}
					) }
					selected={ categoryParent }
					onSelect={ ( item ) => item && setCategoryParent( item ) }
					onRemove={ () => setCategoryParent( null ) }
					onInputChange={ debouncedSearch }
					getFilteredItems={ getFilteredItems }
					getItemLabel={ ( item ) => item?.name || '' }
					getItemValue={ ( item ) => item?.id || '' }
				>
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
