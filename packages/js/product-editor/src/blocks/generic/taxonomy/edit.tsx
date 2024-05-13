/**
 * External dependencies
 */
import type { BlockAttributes } from '@wordpress/blocks';
import {
	createElement,
	useState,
	Fragment,
	useCallback,
	useEffect,
} from '@wordpress/element';
import '@woocommerce/settings';
import { useWooBlockProps } from '@woocommerce/block-templates';
import { __experimentalSelectTreeControl as SelectTreeControl } from '@woocommerce/components';
import { useDebounce, useInstanceId } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { CreateTaxonomyModal } from './create-taxonomy-modal';
import useTaxonomySearch from './use-taxonomy-search';
import type {
	ProductEditorBlockEditProps,
	Taxonomy,
	TaxonomyMetadata,
} from '../../../types';
import useProductEntityProp from '../../../hooks/use-product-entity-prop';

interface TaxonomyBlockAttributes extends BlockAttributes {
	label: string;
	slug: string;
	property: string;
	createTitle: string;
	dialogNameHelpText?: string;
	parentTaxonomyText?: string;
	placeholder?: string;
}

export function Edit( {
	attributes,
	context: { postType, isInSelectedTab },
}: ProductEditorBlockEditProps< TaxonomyBlockAttributes > ) {
	const blockProps = useWooBlockProps( attributes );
	const { hierarchical }: TaxonomyMetadata = useSelect(
		( select ) =>
			// eslint-disable-next-line @typescript-eslint/ban-ts-comment
			// @ts-ignore
			select( 'core' ).getTaxonomy( attributes.slug ) || {
				hierarchical: false,
			}
	);
	const {
		label,
		slug,
		property,
		createTitle,
		dialogNameHelpText,
		parentTaxonomyText,
		disabled,
		placeholder,
	} = attributes;
	const [ searchValue, setSearchValue ] = useState( '' );
	const [ allEntries, setAllEntries ] = useState< Taxonomy[] >( [] );

	const { searchEntity, isResolving } = useTaxonomySearch( slug, {
		fetchParents: hierarchical,
	} );
	const searchDelayed = useDebounce(
		useCallback(
			( val ) => {
				setSearchValue( val );
				searchEntity( val || '' ).then( setAllEntries );
			},
			[ hierarchical ]
		),
		150
	);

	useEffect( () => {
		if ( isInSelectedTab ) {
			searchDelayed( '' );
		}
	}, [ isInSelectedTab ] );

	const [ selectedEntries, setSelectedEntries ] = useProductEntityProp<
		Taxonomy[]
	>( property, { postType, fallbackValue: [] } );

	const mappedEntries = ( selectedEntries || [] ).map( ( b ) => ( {
		value: String( b.id ),
		label: b.name,
	} ) );

	const [ showCreateNewModal, setShowCreateNewModal ] = useState( false );

	const mappedAllEntries = allEntries.map( ( taxonomy ) => ( {
		parent:
			hierarchical && taxonomy.parent && taxonomy.parent > 0
				? String( taxonomy.parent )
				: undefined,
		label: taxonomy.name,
		value: String( taxonomy.id ),
	} ) );

	return (
		<div { ...blockProps }>
			<>
				<SelectTreeControl
					id={
						useInstanceId(
							SelectTreeControl,
							'woocommerce-taxonomy-select'
						) as string
					}
					label={ label }
					isLoading={ isResolving }
					disabled={ disabled }
					multiple
					createValue={ searchValue }
					onInputChange={ searchDelayed }
					placeholder={ placeholder }
					shouldNotRecursivelySelect
					shouldShowCreateButton={ ( typedValue ) =>
						! typedValue ||
						mappedAllEntries.findIndex(
							( taxonomy ) =>
								taxonomy.label.toLowerCase() ===
								typedValue.toLowerCase()
						) === -1
					}
					onCreateNew={ () => setShowCreateNewModal( true ) }
					items={ mappedAllEntries }
					selected={ mappedEntries }
					onSelect={ ( selectedItems ) => {
						if ( Array.isArray( selectedItems ) ) {
							setSelectedEntries( [
								...selectedItems.map( ( i ) => ( {
									id: +i.value,
									name: i.label,
									parent: +( i.parent || 0 ),
								} ) ),
								...( selectedEntries || [] ),
							] );
						} else {
							setSelectedEntries( [
								{
									id: +selectedItems.value,
									name: selectedItems.label,
									parent: +( selectedItems.parent || 0 ),
								},
								...( selectedEntries || [] ),
							] );
						}
					} }
					onRemove={ ( removedItems ) => {
						if ( Array.isArray( removedItems ) ) {
							setSelectedEntries(
								( selectedEntries || [] ).filter(
									( taxonomy ) =>
										! removedItems.find(
											( item ) =>
												item.value ===
												String( taxonomy.id )
										)
								)
							);
						} else {
							setSelectedEntries(
								( selectedEntries || [] ).filter(
									( taxonomy ) =>
										String( taxonomy.id ) !==
										removedItems.value
								)
							);
						}
					} }
				></SelectTreeControl>
				{ showCreateNewModal && (
					<CreateTaxonomyModal
						slug={ slug }
						hierarchical={ hierarchical }
						title={ createTitle }
						dialogNameHelpText={ dialogNameHelpText }
						parentTaxonomyText={ parentTaxonomyText }
						onCancel={ () => setShowCreateNewModal( false ) }
						onCreate={ ( taxonomy ) => {
							setShowCreateNewModal( false );
							setSearchValue( '' );
							setSelectedEntries( [
								{
									id: taxonomy.id,
									name: taxonomy.name,
									parent: taxonomy.parent,
								},
								...( selectedEntries || [] ),
							] );
						} }
						initialName={ searchValue }
					/>
				) }
			</>
		</div>
	);
}
