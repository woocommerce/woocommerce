/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	Button,
	Modal,
	Spinner,
	TextControl,
	Popover,
} from '@wordpress/components';
import { useMemo, useRef, useState } from '@wordpress/element';
import { useDispatch } from '@wordpress/data';
import {
	__experimentalSelectControl as SelectControl,
	__experimentalSelectControlItem as SelectControlItem,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	EXPERIMENTAL_PRODUCT_CATEGORIES_STORE_NAME,
	ProductCategory,
} from '@woocommerce/data';
import classnames from 'classnames';
import { debounce } from 'lodash';

/**
 * Internal dependencies
 */
import './create-category-modal.scss';
import { useCategorySearch } from './use-category-search';
import { CategoryFieldItem } from './category-field-item';

type CreateCategoryModalProps = {
	initialCategoryName?: string;
	onCancel: () => void;
	onCreated: ( newCategory: ProductCategory ) => void;
};

export const CreateCategoryModal: React.FC< CreateCategoryModalProps > = ( {
	initialCategoryName,
	onCancel,
	onCreated,
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
		useState< SelectControlItem | null >( null );
	const selectControlMenuRef = useRef< HTMLElement >( null );

	const onSave = async () => {
		recordEvent( 'product_category_add', {
			new_product_page: true,
		} );
		setIsCreating( true );
		try {
			const newCategory: ProductCategory = await createProductCategory( {
				name: categoryName,
				parent: categoryParent ? categoryParent.value : undefined,
			} );
			invalidateResolutionForStoreSelector( 'getProductCategories' );
			setIsCreating( false );
			onCreated( newCategory );
		} catch ( e ) {
			createNotice(
				'error',
				__( 'Failed to create category.', 'woocommerce' )
			);
			setIsCreating( false );
			onCancel();
		}
	};

	const onInputChange = ( searchString?: string ) => {
		searchCategories( searchString || '' );
	};

	const searchDelayed = useMemo(
		() => debounce( onInputChange, 150 ),
		[ onInputChange ]
	);

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
				<SelectControl
					items={ categoriesSelectList }
					label={ __( 'Parent category (optional)', 'woocommerce' ) }
					selected={ categoryParent }
					onSelect={ ( item: SelectControlItem ) =>
						item && setCategoryParent( item )
					}
					onRemove={ () => setCategoryParent( null ) }
					onInputChange={ searchDelayed }
					getFilteredItems={ getFilteredItems }
				>
					{ ( {
						items,
						isOpen,
						getMenuProps,
						selectItem,
						highlightedIndex,
					} ) => {
						return (
							<div
								{ ...getMenuProps( {
									ref: selectControlMenuRef,
								} ) }
								className={ classnames(
									'woocommerce-select-control__menu',
									{
										'is-open': isOpen,
										'category-field-dropdown__menu': isOpen,
									}
								) }
							>
								{ isOpen && (
									<Popover
										focusOnMount={ false }
										className="woocommerce-select-control__popover-menu"
										position="bottom center"
										anchorRect={
											selectControlMenuRef.current
												? selectControlMenuRef.current.getBoundingClientRect()
												: undefined
										}
									>
										{ /* eslint-disable-next-line jsx-a11y/no-static-element-interactions */ }
										<div
											className="woocommerce-select-control__popover-menu-container"
											style={ {
												width: selectControlMenuRef.current?.getBoundingClientRect()
													.width,
											} }
											onMouseUp={ ( e ) =>
												// Fix to prevent select control dropdown from closing when selecting within the Popover.
												e.stopPropagation()
											}
										>
											{ isOpen &&
												isSearching &&
												items.length === 0 && (
													<div className="category-field-dropdown__item">
														<div className="category-field-dropdown__item-content">
															<Spinner />
														</div>
													</div>
												) }
											{ isOpen &&
												items
													.filter(
														( item ) =>
															categoryTreeKeyValues[
																parseInt(
																	item.value,
																	10
																)
															]?.parentID === 0
													)
													.map(
														(
															item: SelectControlItem
														) => {
															return (
																<CategoryFieldItem
																	key={ `${ item.value }` }
																	item={
																		categoryTreeKeyValues[
																			parseInt(
																				item.value,
																				10
																			)
																		]
																	}
																	selectControlItem={
																		item
																	}
																	onSelect={
																		selectItem
																	}
																	selectedIds={
																		categoryParent
																			? [
																					parseInt(
																						categoryParent.value,
																						10
																					),
																			  ]
																			: []
																	}
																	items={
																		items
																	}
																	highlightedIndex={
																		highlightedIndex
																	}
																/>
															);
														}
													) }
										</div>
									</Popover>
								) }
							</div>
						);
					} }
				</SelectControl>
				<div className="woocommerce-create-new-category-modal__buttons">
					<Button isSecondary onClick={ () => onCancel() }>
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
