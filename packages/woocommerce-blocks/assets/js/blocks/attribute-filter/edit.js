/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { Fragment, useState, useCallback } from '@wordpress/element';
import { InspectorControls, BlockControls } from '@wordpress/block-editor';
import {
	Placeholder,
	Disabled,
	PanelBody,
	ToggleControl,
	Button,
	Toolbar,
	withSpokenMessages,
} from '@wordpress/components';
import Gridicon from 'gridicons';
import { SearchListControl } from '@woocommerce/components';
import { mapValues, toArray, sortBy, find } from 'lodash';
import { ATTRIBUTES } from '@woocommerce/block-settings';
import { getAdminLink } from '@woocommerce/settings';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';
import BlockTitle from '@woocommerce/block-components/block-title';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';
import { IconExternal } from '../../components/icons';
import ToggleButtonControl from '../../components/toggle-button-control';

const Edit = ( { attributes, setAttributes, debouncedSpeak } ) => {
	const {
		attributeId,
		className,
		heading,
		headingLevel,
		isPreview,
		queryType,
		showCounts,
	} = attributes;

	const [ isEditing, setIsEditing ] = useState(
		! attributeId && ! isPreview
	);

	const getBlockControls = () => {
		return (
			<BlockControls>
				<Toolbar
					controls={ [
						{
							icon: 'edit',
							title: __( 'Edit', 'woocommerce' ),
							onClick: () => setIsEditing( ! isEditing ),
							isActive: isEditing,
						},
					] }
				/>
			</BlockControls>
		);
	};

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Content', 'woocommerce' ) }
				>
					<ToggleControl
						label={ __(
							'Product count',
							'woocommerce'
						) }
						help={
							showCounts
								? __(
										'Product counts are visible.',
										'woocommerce'
								  )
								: __(
										'Product counts are hidden.',
										'woocommerce'
								  )
						}
						checked={ showCounts }
						onChange={ () =>
							setAttributes( {
								showCounts: ! showCounts,
							} )
						}
					/>
					<p>
						{ __(
							'Heading Level',
							'woocommerce'
						) }
					</p>
					<HeadingToolbar
						isCollapsed={ false }
						minLevel={ 2 }
						maxLevel={ 7 }
						selectedLevel={ headingLevel }
						onChange={ ( newLevel ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __(
						'Block Settings',
						'woocommerce'
					) }
				>
					<ToggleButtonControl
						label={ __(
							'Query Type',
							'woocommerce'
						) }
						help={
							queryType === 'and'
								? __(
										'Products that have all of the selected attributes will be shown.',
										'woocommerce'
								  )
								: __(
										'Products that have any of the selected attributes will be shown.',
										'woocommerce'
								  )
						}
						value={ queryType }
						options={ [
							{
								label: __(
									'And',
									'woocommerce'
								),
								value: 'and',
							},
							{
								label: __(
									'Or',
									'woocommerce'
								),
								value: 'or',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( {
								queryType: value,
							} )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __(
						'Filter Products by Attribute',
						'woocommerce'
					) }
					initialOpen={ false }
				>
					{ renderAttributeControl() }
				</PanelBody>
			</InspectorControls>
		);
	};

	const noAttributesPlaceholder = () => (
		<Placeholder
			className="wc-block-attribute-filter"
			icon={ <Gridicon icon="menus" /> }
			label={ __(
				'Filter Products by Attribute',
				'woocommerce'
			) }
			instructions={ __(
				'Display a list of filters based on a chosen attribute.',
				'woocommerce'
			) }
		>
			<p>
				{ __(
					"Attributes are needed for filtering your products. You haven't created any attributes yet.",
					'woocommerce'
				) }
			</p>
			<Button
				className="wc-block-attribute-filter__add_attribute_button"
				isDefault
				isLarge
				href={ getAdminLink(
					'edit.php?post_type=product&page=product_attributes'
				) }
			>
				{ __( 'Add new attribute', 'woocommerce' ) +
					' ' }
				<IconExternal />
			</Button>
			<Button
				className="wc-block-attribute-filter__read_more_button"
				isTertiary
				href="https://docs.woocommerce.com/document/managing-product-taxonomies/"
			>
				{ __( 'Learn more', 'woocommerce' ) }
			</Button>
		</Placeholder>
	);

	const onDone = useCallback( () => {
		setIsEditing( false );
		debouncedSpeak(
			__(
				'Showing attribute filter block preview.',
				'woocommerce'
			)
		);
	}, [] );

	const onChange = useCallback(
		( selected ) => {
			if ( ! selected || ! selected.length ) {
				return;
			}

			const selectedId = selected[ 0 ].id;
			const productAttribute = find( ATTRIBUTES, [
				'attribute_id',
				selectedId.toString(),
			] );

			if ( ! productAttribute || attributeId === selectedId ) {
				return;
			}

			const attributeName = productAttribute.attribute_label;

			setAttributes( {
				attributeId: selectedId,
				heading: sprintf(
					// Translators: %s attribute name.
					__( 'Filter by %s', 'woocommerce' ),
					attributeName
				),
			} );
		},
		[ attributeId ]
	);

	const renderAttributeControl = () => {
		const messages = {
			clear: __(
				'Clear selected attribute',
				'woocommerce'
			),
			list: __( 'Product Attributes', 'woocommerce' ),
			noItems: __(
				"Your store doesn't have any product attributes.",
				'woocommerce'
			),
			search: __(
				'Search for a product attribute:',
				'woocommerce'
			),
			selected: ( n ) =>
				sprintf(
					_n(
						'%d attribute selected',
						'%d attributes selected',
						n,
						'woocommerce'
					),
					n
				),
			updated: __(
				'Product attribute search results updated.',
				'woocommerce'
			),
		};

		const list = sortBy(
			toArray(
				mapValues( ATTRIBUTES, ( item ) => {
					return {
						id: parseInt( item.attribute_id, 10 ),
						name: item.attribute_label,
					};
				} )
			),
			'name'
		);

		return (
			<SearchListControl
				className="woocommerce-product-attributes"
				list={ list }
				selected={ list.filter( ( { id } ) => id === attributeId ) }
				onChange={ onChange }
				messages={ messages }
				isSingle
			/>
		);
	};

	const renderEditMode = () => {
		return (
			<Placeholder
				className="wc-block-attribute-filter"
				icon={ <Gridicon icon="menus" /> }
				label={ __(
					'Filter Products by Attribute',
					'woocommerce'
				) }
				instructions={ __(
					'Display a list of filters based on a chosen attribute.',
					'woocommerce'
				) }
			>
				<div className="wc-block-attribute-filter__selection">
					{ renderAttributeControl() }
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	return Object.keys( ATTRIBUTES ).length === 0 ? (
		noAttributesPlaceholder()
	) : (
		<Fragment>
			{ getBlockControls() }
			{ getInspectorControls() }
			{ isEditing ? (
				renderEditMode()
			) : (
				<div className={ className }>
					<BlockTitle
						headingLevel={ headingLevel }
						heading={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<Disabled>
						<Block attributes={ attributes } isEditor />
					</Disabled>
				</div>
			) }
		</Fragment>
	);
};

export default withSpokenMessages( Edit );
