/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import {
	BlockControls,
	useBlockProps,
	InnerBlocks,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import {
	useCollection,
	useCollectionData,
} from '@woocommerce/base-context/hooks';
import {
	AttributeSetting,
	AttributeTerm,
	objectHasProp,
} from '@woocommerce/types';
import {
	Disabled,
	Button,
	ToolbarGroup,
	withSpokenMessages,
	Notice,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps, isAttributeCounts } from './types';
import {
	NoAttributesPlaceholder,
	AttributesPlaceholder,
} from './components/placeholder';
import { AttributeSelectControls } from './components/attribute-select-controls';
import { getAttributeFromId } from './utils';
import { Inspector } from './components/inspector-controls';
import './style.scss';
import { FilterOption } from '../../types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const Toolbar = ( {
	onClick,
	isEditing,
}: {
	onClick: () => void;
	isEditing: boolean;
} ) => (
	<BlockControls>
		<ToolbarGroup
			controls={ [
				{
					icon: 'edit',
					title: __( 'Edit', 'woocommerce' ),
					onClick,
					isActive: isEditing,
				},
			] }
		/>
	</BlockControls>
);

const Wrapper = ( {
	children,
	onClickToolbarEdit,
	isEditing,
	blockProps,
}: {
	children: React.ReactNode;
	onClickToolbarEdit: () => void;
	isEditing: boolean;
	blockProps: object;
} ) => (
	<div { ...blockProps }>
		<Toolbar onClick={ onClickToolbarEdit } isEditing={ isEditing } />
		{ children }
	</div>
);

const AttributeSelectPlaceholder = ( {
	attributeId,
	setAttributeId,
	onClickDone,
}: {
	attributeId: number;
	setAttributeId: ( id: number ) => void;
	onClickDone: () => void;
} ) => (
	<AttributesPlaceholder>
		<div className="wc-block-attribute-filter__selection">
			<AttributeSelectControls
				isCompact={ false }
				attributeId={ attributeId }
				setAttributeId={ setAttributeId }
			/>
			<Button variant="primary" onClick={ onClickDone }>
				{ __( 'Done', 'woocommerce' ) }
			</Button>
		</div>
	</AttributesPlaceholder>
);

function formatFilterOptions(
	attributeOptions: AttributeTerm[]
): FilterOption[] {
	return attributeOptions.map( ( option ) => ( {
		id: `${ option.slug }-${ option.id }`,
		label: option.name,
		value: option.slug,
		count: option.count,
		attrs: option,
		checked: false,
	} ) );
}

const Edit = ( props: EditProps ) => {
	const {
		attributes: blockAttributes,
		setAttributes,
		debouncedSpeak,
	} = props;

	const { attributeId, queryType, isPreview, displayStyle } = blockAttributes;

	const attributeObject = getAttributeFromId( attributeId );

	const [ isEditing, setIsEditing ] = useState(
		! attributeId && ! isPreview
	);
	const [ attributeOptions, setAttributeOptions ] = useState<
		AttributeTerm[]
	>( [] );

	const { results: attributeTerms } = useCollection< AttributeTerm >( {
		namespace: '/wc/store/v1',
		resourceName: 'products/attributes/terms',
		resourceValues: [ attributeObject?.id || 0 ],
		shouldSelect: blockAttributes.attributeId > 0,
		query: { orderby: 'menu_order' },
	} );

	const { results: filteredCounts } = useCollectionData( {
		queryAttribute: {
			taxonomy: attributeObject?.taxonomy || '',
			queryType,
		},
		queryState: {},
		isEditor: true,
	} );

	const blockProps = useBlockProps();

	useEffect( () => {
		const termIdHasProducts =
			objectHasProp( filteredCounts, 'attribute_counts' ) &&
			isAttributeCounts( filteredCounts.attribute_counts )
				? filteredCounts.attribute_counts.map( ( term ) => term.term )
				: [];

		if ( termIdHasProducts.length === 0 ) return setAttributeOptions( [] );

		setAttributeOptions(
			attributeTerms.filter( ( term ) => {
				return termIdHasProducts.includes( term.id );
			} )
		);
	}, [ attributeTerms, filteredCounts ] );

	const onClickDone = useCallback( () => {
		setIsEditing( false );
		debouncedSpeak(
			__(
				'Now displaying a preview of the Filter Products by Attribute block.',
				'woocommerce'
			)
		);
	}, [ setIsEditing ] );

	const setAttributeId = useCallback(
		( id ) => {
			console.log( 'setAttributeId', id );
			setAttributes( {
				attributeId: id,
			} );
		},
		[ setAttributes ]
	);

	const toggleEditing = useCallback( () => {
		setIsEditing( ! isEditing );
	}, [ isEditing ] );

	// Block rendering starts.
	if ( Object.keys( ATTRIBUTES ).length === 0 )
		return (
			<Wrapper
				onClickToolbarEdit={ toggleEditing }
				isEditing={ isEditing }
				blockProps={ blockProps }
			>
				<NoAttributesPlaceholder />
			</Wrapper>
		);

	if ( isEditing )
		return (
			<Wrapper
				onClickToolbarEdit={ toggleEditing }
				isEditing={ isEditing }
				blockProps={ blockProps }
			>
				<AttributeSelectPlaceholder
					onClickDone={ onClickDone }
					attributeId={ attributeId }
					setAttributeId={ setAttributeId }
				/>
			</Wrapper>
		);

	if ( ! attributeId || ! attributeObject )
		return (
			<Wrapper
				onClickToolbarEdit={ toggleEditing }
				isEditing={ isEditing }
				blockProps={ blockProps }
			>
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'Please select an attribute to use this filter!',
							'woocommerce'
						) }
					</p>
				</Notice>
			</Wrapper>
		);

	if ( attributeOptions.length === 0 )
		return (
			<Wrapper
				onClickToolbarEdit={ toggleEditing }
				isEditing={ isEditing }
				blockProps={ blockProps }
			>
				<Notice status="warning" isDismissible={ false }>
					<p>
						{ __(
							'There are no products with the selected attributes.',
							'woocommerce'
						) }
					</p>
				</Notice>
			</Wrapper>
		);

	return (
		<Wrapper
			onClickToolbarEdit={ toggleEditing }
			isEditing={ isEditing }
			blockProps={ blockProps }
		>
			<Inspector { ...props } />
			<BlockContextProvider
				value={ {
					filterOptions: formatFilterOptions( attributeOptions ),
					attributeTerms,
				} }
			>
				<InnerBlocks
					template={ [ [ displayStyle ] ] }
					renderAppender={ () => null }
				/>
			</BlockContextProvider>
		</Wrapper>
	);
};

export default withSpokenMessages( Edit );
