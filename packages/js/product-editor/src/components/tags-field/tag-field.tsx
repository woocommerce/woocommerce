/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	useEffect,
	useState,
	createElement,
	Fragment,
} from '@wordpress/element';
import {
	TreeItemType,
	__experimentalSelectTreeControl as SelectTree,
} from '@woocommerce/components';
import { recordEvent } from '@woocommerce/tracks';
import {
	EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME,
	ProductTag,
} from '@woocommerce/data';
import { useDispatch } from '@wordpress/data';
import { useDebounce } from '@wordpress/compose';

/**
 * Internal dependencies
 */
import { useTagSearch, ProductTagNode } from './use-tag-search';
import { TRACKS_SOURCE } from '../../constants';
import { CreateTagModal } from './create-tag-modal';

type TagFieldProps = {
	id: string;
	isVisible?: boolean;
	label: string;
	placeholder: string;
	value?: ProductTagNode[];
	onChange: ( value: ProductTagNode[] ) => void;
};

export function mapFromTagToTreeItem( val: ProductTagNode ): TreeItemType {
	return {
		value: String( val.id ),
		label: val.name,
	};
}

export function mapFromTreeItemToTag( val: TreeItemType ): ProductTagNode {
	return {
		id: +val.value,
		name: val.label,
	};
}

export function mapFromTagsToTreeItems(
	tags: ProductTagNode[]
): TreeItemType[] {
	return tags.map( mapFromTagToTreeItem );
}

export function mapFromTreeItemsToTags(
	tags: TreeItemType[]
): ProductTagNode[] {
	return tags.map( mapFromTreeItemToTag );
}

export const TagField: React.FC< TagFieldProps > = ( {
	id,
	isVisible = false,
	label,
	placeholder,
	value = [],
	onChange,
} ) => {
	const { tagsSelectList, searchTags } = useTagSearch();
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ isCreating, setIsCreating ] = useState( false );
	const [ showCreateNewModal, setShowCreateNewModal ] = useState( false );
	const [ newInputValue, setNewInputValue ] = useState<
		undefined | string
	>();
	const { createProductTag, invalidateResolutionForStoreSelector } =
		useDispatch( EXPERIMENTAL_PRODUCT_TAGS_STORE_NAME );
	const { createNotice } = useDispatch( 'core/notices' );

	const onInputChange = ( searchString?: string ) => {
		setSearchValue( searchString || '' );
		searchTags( searchString || '' );
		setNewInputValue( searchString );
	};

	useEffect( () => {
		if ( isVisible ) {
			searchTags();
		}
	}, [ isVisible ] );

	const searchDelayed = useDebounce( onInputChange, 150 );

	const onSave = async () => {
		recordEvent( 'product_tag_add', {
			source: TRACKS_SOURCE,
		} );
		setIsCreating( true );
		try {
			setNewInputValue( '' );
			const newTag: ProductTag = await createProductTag( {
				name: searchValue,
			} );
			invalidateResolutionForStoreSelector( 'getProductTags' );
			setIsCreating( false );
			onChange( [ ...value, newTag ] );
			onInputChange( '' );
		} catch ( e ) {
			createNotice(
				'error',
				__( 'Failed to create tag.', 'woocommerce' )
			);
			setIsCreating( false );
		}
	};

	return (
		<>
			<SelectTree
				id={ id }
				multiple
				shouldNotRecursivelySelect
				createValue={ searchValue }
				label={ label }
				isLoading={ isCreating }
				onInputChange={ searchDelayed }
				placeholder={ value.length === 0 ? placeholder : '' }
				initialInputValue={ newInputValue }
				onCreateNew={
					searchValue.length === 0
						? () => setShowCreateNewModal( true )
						: onSave
				}
				shouldShowCreateButton={ ( typedValue ) =>
					! typedValue ||
					tagsSelectList.findIndex(
						( item ) => item.name === typedValue
					) === -1
				}
				items={ mapFromTagsToTreeItems( tagsSelectList ) }
				selected={ mapFromTagsToTreeItems( value ) }
				onSelect={ ( selectedItems ) => {
					if ( Array.isArray( selectedItems ) ) {
						const newItems: ProductTagNode[] =
							mapFromTreeItemsToTags(
								selectedItems.filter(
									( { value: selectedItemValue } ) =>
										! value.some(
											( item ) =>
												item.id === +selectedItemValue
										)
								)
							);
						onChange( [ ...value, ...newItems ] );
					}
				} }
				onRemove={ ( removedItems ) => {
					const newValues = Array.isArray( removedItems )
						? value.filter(
								( item ) =>
									! removedItems.some(
										( { value: removedValue } ) =>
											item.id === +removedValue
									)
						  )
						: value.filter(
								( item ) => item.id !== +removedItems.value
						  );
					onChange( newValues );
				} }
			></SelectTree>
			{ showCreateNewModal && (
				<CreateTagModal
					initialTagName={ searchValue }
					onCancel={ () => setShowCreateNewModal( false ) }
					onCreate={ ( newTag ) => {
						onChange( [ ...value, newTag ] );
						setShowCreateNewModal( false );
						onInputChange( '' );
					} }
				/>
			) }
		</>
	);
};
