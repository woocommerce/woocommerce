/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal, TextControl } from '@wordpress/components';
import { useDebounce } from '@wordpress/compose';
import { useState, createElement } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	__experimentalSelectTreeControl as SelectTree,
	__experimentalSelectTreeMenuSlot as SelectTreeMenuSlot,
	TreeItemType as Item,
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
import { mapFromCategoryType } from './category-field';

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
		searchCategories,
		getFilteredItemsForSelectTree,
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

	const [ categoryParentTypedValue, setCategoryParentTypedValue ] =
		useState< string >( '' );

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
				<SelectTree
					label={ __( 'Parent category (optional)', 'woocommerce' ) }
					id="parent-category-field"
					isLoading={ isSearching }
					items={ getFilteredItemsForSelectTree(
						mapFromCategoryType( categoriesSelectList ),
						categoryParentTypedValue,
						[]
					) }
					shouldNotRecursivelySelect
					selected={
						categoryParent
							? {
									label: String( categoryParent?.name ),
									value: String( categoryParent?.id ),
							  }
							: undefined
					}
					onSelect={ ( item: Item ) =>
						item &&
						setCategoryParent( {
							id: +item.value,
							name: item.label,
							parent: item.parent ? +item.parent : 0,
						} )
					}
					onRemove={ () => setCategoryParent( null ) }
					onInputChange={ ( value ) => {
						debouncedSearch( value );
						setCategoryParentTypedValue( value || '' );
					} }
					createValue={ categoryParentTypedValue }
				/>
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
			<SelectTreeMenuSlot />
		</Modal>
	);
};
