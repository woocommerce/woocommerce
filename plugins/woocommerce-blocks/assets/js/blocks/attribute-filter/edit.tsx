/**
 * External dependencies
 */
import { __, sprintf, _n } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import {
	InspectorControls,
	BlockControls,
	useBlockProps,
} from '@wordpress/block-editor';
import { Icon, category, external } from '@wordpress/icons';
import { SearchListControl } from '@woocommerce/editor-components/search-list-control';
import { sortBy } from 'lodash';
import { getAdminLink, getSetting } from '@woocommerce/settings';
import HeadingToolbar from '@woocommerce/editor-components/heading-toolbar';
import BlockTitle from '@woocommerce/editor-components/block-title';
import classnames from 'classnames';
import { SearchListItemsType } from '@woocommerce/editor-components/search-list-control/types';
import { AttributeSetting } from '@woocommerce/types';
import {
	Placeholder,
	Disabled,
	PanelBody,
	ToggleControl,
	Button,
	ToolbarGroup,
	withSpokenMessages,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControl as ToggleGroupControl,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToggleGroupControlOption as ToggleGroupControlOption,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import './editor.scss';
import type { EditProps } from './types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const Edit = ( { attributes, setAttributes, debouncedSpeak }: EditProps ) => {
	const {
		attributeId,
		className,
		displayStyle,
		heading,
		headingLevel,
		isPreview,
		queryType,
		showCounts,
		showFilterButton,
		selectType,
	} = attributes;

	const [ isEditing, setIsEditing ] = useState(
		! attributeId && ! isPreview
	);

	const blockProps = useBlockProps();

	const getBlockControls = () => {
		return (
			<BlockControls>
				<ToolbarGroup
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

	const onChange = ( selected: SearchListItemsType ) => {
		if ( ! selected || ! selected.length ) {
			return;
		}

		const selectedId = selected[ 0 ].id;
		const productAttribute = ATTRIBUTES.find(
			( attribute ) => attribute.attribute_id === selectedId.toString()
		);

		if ( ! productAttribute || attributeId === selectedId ) {
			return;
		}

		const attributeName = productAttribute.attribute_label;

		setAttributes( {
			attributeId: selectedId as number,
			heading: sprintf(
				/* translators: %s attribute name. */
				__( 'Filter by %s', 'woo-gutenberg-products-block' ),
				attributeName
			),
		} );
	};

	const renderAttributeControl = ( {
		isCompact,
	}: {
		isCompact: boolean;
	} ) => {
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
			selected: ( n: number ) =>
				sprintf(
					/* translators: %d is the number of attributes selected. */
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
			ATTRIBUTES.map( ( item ) => {
				return {
					id: parseInt( item.attribute_id, 10 ),
					name: item.attribute_label,
				};
			} ),
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
				isCompact={ isCompact }
			/>
		);
	};

	const getInspectorControls = () => {
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
										'Product count is visible.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Product count is hidden.',
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
						selectedLevel={ headingLevel }
						onChange={ ( newLevel: number ) =>
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
					<ToggleGroupControl
						label={ __(
							'Allow selecting multiple options?',
							'woo-gutenberg-products-block'
						) }
						value={ selectType || 'multiple' }
						onChange={ ( value: string ) =>
							setAttributes( {
								selectType: value,
							} )
						}
					>
						<ToggleGroupControlOption
							value="multiple"
							label={ __(
								'Multiple',
								'woo-gutenberg-products-block'
							) }
						/>
						<ToggleGroupControlOption
							value="single"
							label={ __(
								'Single',
								'woo-gutenberg-products-block'
							) }
						/>
					</ToggleGroupControl>
					{ selectType === 'multiple' && (
						<ToggleGroupControl
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
							onChange={ ( value: string ) =>
								setAttributes( {
									queryType: value,
								} )
							}
						>
							<ToggleGroupControlOption
								value="and"
								label={ __(
									'And',
									'woo-gutenberg-products-block'
								) }
							/>
							<ToggleGroupControlOption
								value="or"
								label={ __(
									'Or',
									'woo-gutenberg-products-block'
								) }
							/>
						</ToggleGroupControl>
					) }
					<ToggleGroupControl
						label={ __(
							'Display Style',
							'woo-gutenberg-products-block'
						) }
						value={ displayStyle }
						onChange={ ( value: string ) =>
							setAttributes( {
								displayStyle: value,
							} )
						}
					>
						<ToggleGroupControlOption
							value="list"
							label={ __(
								'List',
								'woo-gutenberg-products-block'
							) }
						/>
						<ToggleGroupControlOption
							value="dropdown"
							label={ __(
								'Dropdown',
								'woo-gutenberg-products-block'
							) }
						/>
					</ToggleGroupControl>
					<ToggleControl
						label={ __(
							'Filter button',
							'woo-gutenberg-products-block'
						) }
						help={
							showFilterButton
								? __(
										'Products will only update when the button is pressed.',
										'woo-gutenberg-products-block'
								  )
								: __(
										'Products will update as options are selected.',
										'woo-gutenberg-products-block'
								  )
						}
						checked={ showFilterButton }
						onChange={ ( value ) =>
							setAttributes( {
								showFilterButton: value,
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
					{ renderAttributeControl( { isCompact: true } ) }
				</PanelBody>
			</InspectorControls>
		);
	};

	const noAttributesPlaceholder = () => (
		<Placeholder
			className="wc-block-attribute-filter"
			icon={ <Icon icon={ category } /> }
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
					"Attributes are needed for filtering your products. You haven't created any attributes yet.",
					'woo-gutenberg-products-block'
				) }
			</p>
			<Button
				className="wc-block-attribute-filter__add-attribute-button"
				isSecondary
				href={ getAdminLink(
					'edit.php?post_type=product&page=product_attributes'
				) }
			>
				{ __( 'Add new attribute', 'woo-gutenberg-products-block' ) +
					' ' }
				<Icon icon={ external } />
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

	const onDone = () => {
		setIsEditing( false );
		debouncedSpeak(
			__(
				'Showing Filter Products by Attribute block preview.',
				'woo-gutenberg-products-block'
			)
		);
	};

	const renderEditMode = () => {
		return (
			<Placeholder
				className="wc-block-attribute-filter"
				icon={ <Icon icon={ category } /> }
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
					{ renderAttributeControl( { isCompact: false } ) }
					<Button isPrimary onClick={ onDone }>
						{ __( 'Done', 'woo-gutenberg-products-block' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	return Object.keys( ATTRIBUTES ).length === 0 ? (
		noAttributesPlaceholder()
	) : (
		<div { ...blockProps }>
			{ getBlockControls() }
			{ getInspectorControls() }
			{ isEditing ? (
				renderEditMode()
			) : (
				<div
					className={ classnames(
						className,
						'wc-block-attribute-filter'
					) }
				>
					<BlockTitle
						className="wc-block-attribute-filter__title"
						headingLevel={ headingLevel }
						heading={ heading }
						onChange={ ( value: string ) =>
							setAttributes( { heading: value } )
						}
					/>
					<Disabled>
						<Block attributes={ attributes } isEditor />
					</Disabled>
				</div>
			) }
		</div>
	);
};

export default withSpokenMessages( Edit );
