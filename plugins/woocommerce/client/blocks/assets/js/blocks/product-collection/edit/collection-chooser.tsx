/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { Button, Dropdown, Icon, Tooltip } from '@wordpress/components';
import { useResizeObserver } from '@wordpress/compose';
import { __ } from '@wordpress/i18n';
import {
	BlockInstance,
	createBlock,
	createBlocksFromInnerBlocksTemplate,
	store as blocksStore,
	BlockVariation,
	BlockIcon,
} from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import {
	type CollectionName,
	type ProductCollectionAttributes,
	CoreCollectionNames,
} from '../types';
import blockJson from '../block.json';
import { getCollectionByName } from '../collections';
import { getDefaultProductCollection } from '../utils';

type CollectionButtonProps = {
	title: string;
	icon: BlockIcon | undefined;
	description: string | undefined;
	onClick: () => void;
};

type CollectionOptionsProps = {
	chosenCollection?: CollectionName | undefined;
	catalogVariation: BlockVariation;
	collectionVariations: BlockVariation[];
	onCollectionClick: ( name: string ) => void;
};

export const applyCollection = (
	collectionName: CollectionName,
	setAttributes: ( attrs: Partial< ProductCollectionAttributes > ) => void,
	replaceInnerBlocks: (
		clientId: string,
		innerBlocks: BlockInstance[]
	) => void,
	clientId: string
) => {
	const collection = getCollectionByName( collectionName );

	if ( ! collection ) {
		return;
	}

	// `createBlock` merges the collection's attribute overrides with the
	// block type's registered defaults, guaranteeing a clean, fully-populated
	// attributes object. This matters because collection variations only
	// declare the attributes they override (e.g. "By Category" doesn't
	// declare a `query` key at all) — spreading `collection.attributes`
	// directly onto `setAttributes` would leave any attribute the new
	// collection doesn't mention (e.g. a previous collection's
	// `query.woocommerceHandPickedProducts`) untouched instead of reset.
	const newBlock =
		collection.name === CoreCollectionNames.PRODUCT_CATALOG
			? getDefaultProductCollection()
			: createBlock(
					blockJson.name,
					collection.attributes,
					createBlocksFromInnerBlocksTemplate(
						collection.innerBlocks
					)
			  );

	// Update the block's attributes and inner blocks in place (instead of
	// calling replaceBlock, which swaps in an entirely new block instance)
	// so the change reads as a targeted patch rather than a full block
	// delete + insert.
	setAttributes( {
		...newBlock.attributes,
		collection: collectionName,
	} as Partial< ProductCollectionAttributes > );

	replaceInnerBlocks( clientId, newBlock.innerBlocks );
};

const CollectionButton = ( {
	title,
	icon,
	description,
	onClick,
}: CollectionButtonProps ) => {
	return (
		<Tooltip text={ description } placement="top">
			<Button
				className="wc-blocks-product-collection__collection-button"
				onClick={ onClick }
			>
				<div className="wc-blocks-product-collection__collection-button-icon">
					<Icon icon={ icon as Icon.IconType< BlockIcon > } />
				</div>
				<p className="wc-blocks-product-collection__collection-button-title">
					{ title }
				</p>
			</Button>
		</Tooltip>
	);
};

const CreateCollectionButton = ( props: CollectionButtonProps ) => {
	const { description, onClick } = props;

	return (
		<div className="wc-blocks-product-collection__collections-create">
			<span>{ __( 'or', 'woocommerce' ) }</span>
			<Tooltip text={ description } placement="top">
				<Button onClick={ onClick }>
					{ __( 'create your own', 'woocommerce' ) }
				</Button>
			</Tooltip>
		</div>
	);
};

const GridCollectionOptions = ( props: CollectionOptionsProps ) => {
	const { onCollectionClick, catalogVariation, collectionVariations } = props;

	return (
		<div className="wc-blocks-product-collection__collections-grid">
			<div className="wc-blocks-product-collection__collections-section">
				{ collectionVariations.map(
					( { name, title, icon, description } ) => (
						<CollectionButton
							key={ name }
							title={ title }
							description={ description }
							icon={ icon }
							onClick={ () => onCollectionClick( name ) }
						/>
					)
				) }
			</div>
			<CreateCollectionButton
				title={ catalogVariation.title }
				description={ catalogVariation.description }
				icon={ catalogVariation.icon }
				onClick={ () => onCollectionClick( catalogVariation.name ) }
			/>
		</div>
	);
};

const DropdownCollectionOptions = ( props: CollectionOptionsProps ) => {
	const { onCollectionClick, catalogVariation, collectionVariations } = props;

	return (
		<div className="wc-blocks-product-collection__collections-dropdown">
			<Dropdown
				className="wc-blocks-product-collection__collections-dropdown-toggle"
				contentClassName="wc-blocks-product-collection__collections-dropdown-content"
				renderToggle={ ( { isOpen, onToggle } ) => (
					<Button
						variant="secondary"
						onClick={ onToggle }
						aria-expanded={ isOpen }
					>
						{ __( 'Choose collection', 'woocommerce' ) }
					</Button>
				) }
				renderContent={ () => (
					<>
						{ collectionVariations.map(
							( { name, title, icon, description } ) => (
								<CollectionButton
									key={ name }
									title={ title }
									description={ description }
									icon={ icon }
									onClick={ () => onCollectionClick( name ) }
								/>
							)
						) }
					</>
				) }
			></Dropdown>
			<CreateCollectionButton
				title={ catalogVariation.title }
				description={ catalogVariation.description }
				icon={ catalogVariation.icon }
				onClick={ () => onCollectionClick( catalogVariation.name ) }
			/>
		</div>
	);
};

const CollectionChooser = (
	props: Pick<
		CollectionOptionsProps,
		'chosenCollection' | 'onCollectionClick'
	>
) => {
	// Get Collections
	const blockCollections = useSelect( ( select ) => {
		// @ts-expect-error Type definitions are missing
		// https://github.com/DefinitelyTyped/DefinitelyTyped/blob/master/types/wordpress__blocks/store/selectors.d.ts
		const { getBlockVariations } = select( blocksStore );
		return getBlockVariations( blockJson.name );
	}, [] ) as BlockVariation[];

	const productCatalog = useMemo(
		() =>
			blockCollections.find(
				( { name } ) => name === CoreCollectionNames.PRODUCT_CATALOG
			) as BlockVariation,
		[ blockCollections ]
	);

	const collectionVariations = useMemo(
		() =>
			blockCollections.filter( ( { name, scope } ) => {
				return (
					name !== CoreCollectionNames.PRODUCT_CATALOG &&
					// Display collections in the Collection Chooser if:
					// 1. They have an explicit "block" scope
					// 2. The scope is undefined (scope defaults to both block and inserter)
					( scope === undefined || scope?.includes( 'block' ) )
				);
			} ) as BlockVariation[],
		[ blockCollections ]
	);

	const [ resizeListener, { width } ] = useResizeObserver();

	let OptionsComponent;
	if ( width !== null && width >= 600 ) {
		OptionsComponent = GridCollectionOptions;
	} else {
		OptionsComponent = DropdownCollectionOptions;
	}

	return (
		<>
			{ resizeListener }
			{ !! width && (
				<OptionsComponent
					{ ...props }
					catalogVariation={ productCatalog }
					collectionVariations={ collectionVariations }
				/>
			) }
		</>
	);
};

export default CollectionChooser;
