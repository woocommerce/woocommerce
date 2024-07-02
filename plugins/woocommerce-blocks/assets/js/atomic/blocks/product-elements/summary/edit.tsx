/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import {
	RangeControl,
	ToggleControl,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// @ts-expect-error Using experimental features
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import Block from './block';
import withProductSelector from '../shared/with-product-selector';
import {
	BLOCK_TITLE as title,
	BLOCK_ICON as icon,
	BLOCK_DESCRIPTION as description,
} from './constants';
import './editor.scss';
import { useIsDescendentOfSingleProductBlock } from '../shared/use-is-descendent-of-single-product-block';
import { useIsDescendentOfSingleProductTemplate } from '../shared/use-is-descendent-of-single-product-template';
import type { EditProps, ControlProps } from './types';

const ShowDescriptionIfEmptyControl = ( {
	showDescriptionIfEmpty,
	setAttributes,
}: ControlProps< 'showDescriptionIfEmpty' > ) => {
	const label = __( 'Show description if empty', 'woocommerce' );
	const help = __(
		"Display the product description if it doesn't have a summary",
		'woocommerce'
	);

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => showDescriptionIfEmpty === true }
			isShownByDefault
		>
			<ToggleControl
				label={ label }
				help={ help }
				checked={ showDescriptionIfEmpty }
				onChange={ ( value ) => {
					setAttributes( {
						showDescriptionIfEmpty: value,
					} );
				} }
			/>
		</ToolsPanelItem>
	);
};

const MaxWordCountControl = ( {
	summaryLength,
	setAttributes,
}: ControlProps< 'summaryLength' > ) => {
	const label = __( 'Max word count', 'woocommerce' );
	const help = __( 'Set to 0 to remove the limit completely', 'woocommerce' );

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => summaryLength !== 0 }
			isShownByDefault
		>
			<RangeControl
				label={ label }
				help={ help }
				value={ summaryLength }
				onChange={ ( value ) => {
					setAttributes( {
						summaryLength: value || 0,
					} );
				} }
				min={ 0 }
				max={ 1000 }
				step={ 10 }
			/>
		</ToolsPanelItem>
	);
};

const LinkToDescriptionControl = ( {
	showLink,
	setAttributes,
}: ControlProps< 'showLink' > ) => {
	const label = __( 'Link to description', 'woocommerce' );
	const help = __(
		"Display a button to let shoppers jump to the product's description",
		'woocommerce'
	);

	return (
		<ToolsPanelItem
			label={ label }
			hasValue={ () => showLink === false }
			isShownByDefault
		>
			<ToggleControl
				label={ label }
				help={ help }
				checked={ showLink }
				onChange={ ( value ) => {
					setAttributes( {
						showLink: value,
					} );
				} }
			/>
		</ToolsPanelItem>
	);
};


const Edit = ( {
	attributes,
	context,
	setAttributes,
}: EditProps ): JSX.Element => {
	const blockProps = useBlockProps();
	const { showDescriptionIfEmpty, showLink, summaryLength, linkText } =
		attributes;

	const isDescendentOfQueryLoop = Number.isFinite( context.queryId );
	const { isDescendentOfSingleProductBlock } =
		useIsDescendentOfSingleProductBlock( { blockClientId: blockProps.id } );

	let { isDescendentOfSingleProductTemplate } =
		useIsDescendentOfSingleProductTemplate();

	if ( isDescendentOfQueryLoop ) {
		isDescendentOfSingleProductTemplate = false;
	}

	useEffect(
		() =>
			setAttributes( {
				isDescendentOfQueryLoop,
				isDescendentOfSingleProductTemplate,
				isDescendentOfSingleProductBlock,
			} ),
		[
			setAttributes,
			isDescendentOfQueryLoop,
			isDescendentOfSingleProductTemplate,
			isDescendentOfSingleProductBlock,
		]
	);

	return (
		<div { ...blockProps }>
			<Block { ...attributes } />
			<InspectorControls>
				<ToolsPanel
					label={ __( 'Settings', 'woocommerce' ) }
					resetAll={ () => {
						const defaultSettings = {};
						setAttributes( defaultSettings );
					} }
				>
					<ShowDescriptionIfEmptyControl
						showDescriptionIfEmpty={ showDescriptionIfEmpty }
						setAttributes={ setAttributes }
					/>
					{ showDescriptionIfEmpty && (
						<MaxWordCountControl
							summaryLength={ summaryLength }
							setAttributes={ setAttributes }
						/>
					) }
					<LinkToDescriptionControl
						showLink={ showLink }
						setAttributes={ setAttributes }
					/>
				</ToolsPanel>
			</InspectorControls>
		</div>
	);
};

// @todo: Refactor this to remove the HOC 'withProductSelector()' component as users will not see this block in the inserter. Therefore, we can export the Edit component by default. The HOC 'withProductSelector()' component should also be removed from other `product-elements` components. See also https://github.com/woocommerce/woocommerce-blocks/pull/7566#pullrequestreview-1168635469.
export default withProductSelector( { icon, title, description } )( Edit );
