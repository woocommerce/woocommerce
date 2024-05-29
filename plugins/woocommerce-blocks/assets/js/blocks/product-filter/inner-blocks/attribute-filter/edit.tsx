/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useEffect, useState } from '@wordpress/element';
import { BlockControls, useBlockProps } from '@wordpress/block-editor';
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
import { AttributeCheckboxList } from './components/attribute-checkbox-list';
import { AttributeDropdown } from './components/attribute-dropdown';
import { attributeOptionsPreview } from './constants';
import './style.scss';

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

const Edit = ( props: EditProps ) => {
	const {
		attributes: blockAttributes,
		setAttributes,
		debouncedSpeak,
	} = props;

	const { attributeId, queryType, isPreview, displayStyle, showCounts } =
		blockAttributes;

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
			setAttributes( {
				attributeId: id,
			} );
		},
		[ setAttributes ]
	);

	const toggleEditing = useCallback( () => {
		setIsEditing( ! isEditing );
	}, [ isEditing ] );

	if ( isPreview ) {
		return (
			<Wrapper
				onClickToolbarEdit={ toggleEditing }
				isEditing={ isEditing }
				blockProps={ blockProps }
			>
				<Disabled>
					<AttributeCheckboxList
						showCounts={ showCounts }
						attributeTerms={ attributeOptionsPreview }
					/>
				</Disabled>
			</Wrapper>
		);
	}

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
			<Disabled>
				{ displayStyle === 'dropdown' ? (
					<AttributeDropdown
						label={
							attributeObject.label ||
							__( 'attribute', 'woocommerce' )
						}
					/>
				) : (
					<AttributeCheckboxList
						showCounts={ showCounts }
						attributeTerms={ attributeOptions }
					/>
				) }{ ' ' }
			</Disabled>
		</Wrapper>
	);
};

export default withSpokenMessages( Edit );
