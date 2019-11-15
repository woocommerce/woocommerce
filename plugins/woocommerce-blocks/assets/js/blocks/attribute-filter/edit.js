/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { Fragment, useState, useCallback } from '@wordpress/element';
import {
	InspectorControls,
	BlockControls,
	PlainText,
} from '@wordpress/block-editor';
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
import { getAdminLink } from '@woocommerce/navigation';
import HeadingToolbar from '@woocommerce/block-components/heading-toolbar';

/**
 * Internal dependencies
 */
import Block from './block.js';
import './editor.scss';
import { IconExternal } from '../../components/icons';
import ToggleButtonControl from '../../components/toggle-button-control';

const Edit = ( { attributes, setAttributes, debouncedSpeak } ) => {
	const [ isEditing, setIsEditing ] = useState(
		! attributes.attributeId && ! attributes.isPreview
	);

	const getBlockControls = () => {
		return (
			<BlockControls>
				<Toolbar
					controls={ [
						{
							icon: 'edit',
							title: __( 'Edit', 'woo-gutenberg-products-block' ),
							onClick: () => setIsEditing( ! isEditing ),
							isActive: isEditing,
						},
					] }
				/>
			</BlockControls>
		);
	};

	const getInspectorControls = () => {
		const { showCounts, queryType } = attributes;

		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Content', 'woo-gutenberg-products-block' ) }
				>
					<ToggleControl
						label={ __(
							'Product count',
							'woo-gutenberg-products-block'
						) }
						help={
							showCounts
								? __(
										'Product counts are visible.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Product counts are hidden.',
										'woo-gutenberg-products-block'
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
							'woo-gutenberg-products-block'
						) }
					</p>
					<HeadingToolbar
						isCollapsed={ false }
						minLevel={ 2 }
						maxLevel={ 7 }
						selectedLevel={ attributes.headingLevel }
						onChange={ ( newLevel ) =>
							setAttributes( { headingLevel: newLevel } )
						}
					/>
				</PanelBody>
				<PanelBody
					title={ __(
						'Block Settings',
						'woo-gutenberg-products-block'
					) }
				>
					<ToggleButtonControl
						label={ __(
							'Query Type',
							'woo-gutenberg-products-block'
						) }
						help={
							queryType === 'and'
								? __(
										'Products that have all of the selected attributes will be shown.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Products that have any of the selected attributes will be shown.',
										'woo-gutenberg-products-block'
								  )
						}
						value={ queryType }
						options={ [
							{
								label: __(
									'And',
									'woo-gutenberg-products-block'
								),
								value: 'and',
							},
							{
								label: __(
									'Or',
									'woo-gutenberg-products-block'
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
						'woo-gutenberg-products-block'
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
				'woo-gutenberg-products-block'
			) }
			instructions={ __(
				'Display a list of filters based on a chosen attribute.',
				'woo-gutenberg-products-block'
			) }
		>
			<p>
				{ __(
					"Attributes are needed for filtering your products. You haven't created any products yet.",
					'woo-gutenberg-products-block'
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
				{ __( 'Add new attribute', 'woo-gutenberg-products-block' ) +
					' ' }
				<IconExternal />
			</Button>
			<Button
				className="wc-block-attribute-filter__read_more_button"
				isTertiary
				href="https://docs.woocommerce.com/document/managing-product-taxonomies/"
			>
				{ __( 'Learn more', 'woo-gutenberg-products-block' ) }
			</Button>
		</Placeholder>
	);

	const onDone = useCallback( () => {
		setIsEditing( false );
		debouncedSpeak(
			__(
				'Showing attribute filter block preview.',
				'woo-gutenberg-products-block'
			)
		);
	}, [] );

	const onChange = useCallback( ( selected ) => {
		const selectedId = selected[ 0 ].id;
		const productAttribute = find( ATTRIBUTES, [
			'attribute_id',
			selectedId.toString(),
		] );

		if ( ! productAttribute || attributes.attributeId === selectedId ) {
			return;
		}

		const attributeName = productAttribute.attribute_name;

		setAttributes( {
			attributeId: selectedId,
			heading: sprintf(
				// Translators: %s attribute name.
				__( 'Filter by %s', 'woo-gutenberg-products-block' ),
				attributeName
			),
		} );
	}, [] );

	const renderAttributeControl = () => {
		const { attributeId } = attributes;

		const messages = {
			clear: __(
				'Clear selected attribute',
				'woo-gutenberg-products-block'
			),
			list: __( 'Product Attributes', 'woo-gutenberg-products-block' ),
			noItems: __(
				"Your store doesn't have any product attributes.",
				'woo-gutenberg-products-block'
			),
			search: __(
				'Search for a product attribute:',
				'woo-gutenberg-products-block'
			),
			selected: ( n ) =>
				sprintf(
					_n(
						'%d attribute selected',
						'%d attributes selected',
						n,
						'woo-gutenberg-products-block'
					),
					n
				),
			updated: __(
				'Product attribute search results updated.',
				'woo-gutenberg-products-block'
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
					'woo-gutenberg-products-block'
				) }
				instructions={ __(
					'Display a list of filters based on a chosen attribute.',
					'woo-gutenberg-products-block'
				) }
			>
				<div className="wc-block-attribute-filter__selection">
					{ renderAttributeControl() }
					<Button isDefault onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	const TagName = `h${ attributes.headingLevel }`;

	return Object.keys( ATTRIBUTES ).length === 0 ? (
		noAttributesPlaceholder()
	) : (
		<Fragment>
			{ getBlockControls() }
			{ getInspectorControls() }
			{ isEditing ? (
				renderEditMode()
			) : (
				<Fragment>
					<TagName>
						<PlainText
							className="wc-block-attribute-filter-heading"
							value={ attributes.heading }
							onChange={ ( value ) =>
								setAttributes( { heading: value } )
							}
						/>
					</TagName>
					<Disabled>
						<Block attributes={ attributes } isEditor />
					</Disabled>
				</Fragment>
			) }
		</Fragment>
	);
};

export default withSpokenMessages( Edit );
