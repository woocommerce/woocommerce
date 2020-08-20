/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	Button,
	PanelBody,
	Placeholder,
	withSpokenMessages,
} from '@wordpress/components';
import { SearchListItem } from '@woocommerce/components';
import { Fragment } from '@wordpress/element';
import PropTypes from 'prop-types';
import ProductControl from '@woocommerce/block-components/product-control';
import { Icon, comment } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import EditorContainerBlock from '../editor-container-block.js';
import NoReviewsPlaceholder from './no-reviews-placeholder.js';
import {
	getBlockControls,
	getSharedReviewContentControls,
	getSharedReviewListControls,
} from '../edit-utils.js';

/**
 * Component to handle edit mode of "Reviews by Product".
 */
const ReviewsByProductEditor = ( {
	attributes,
	debouncedSpeak,
	setAttributes,
} ) => {
	const { editMode, productId } = attributes;

	const renderProductControlItem = ( args ) => {
		const { item = 0 } = args;

		return (
			<SearchListItem
				{ ...args }
				countLabel={ sprintf(
					_n(
						'%d Review',
						'%d Reviews',
						item.review_count,
						'woocommerce'
					),
					item.review_count
				) }
				showCount
				aria-label={ sprintf(
					_n(
						'%s, has %d review',
						'%s, has %d reviews',
						item.review_count,
						'woocommerce'
					),
					item.name,
					item.review_count
				) }
			/>
		);
	};

	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<PanelBody
					title={ __( 'Product', 'woocommerce' ) }
					initialOpen={ false }
				>
					<ProductControl
						selected={ attributes.productId || 0 }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
						} }
						renderItem={ renderProductControlItem }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Content', 'woocommerce' ) }
				>
					{ getSharedReviewContentControls(
						attributes,
						setAttributes
					) }
				</PanelBody>
				<PanelBody
					title={ __(
						'List Settings',
						'woocommerce'
					) }
				>
					{ getSharedReviewListControls( attributes, setAttributes ) }
				</PanelBody>
			</InspectorControls>
		);
	};

	const renderEditMode = () => {
		const onDone = () => {
			setAttributes( { editMode: false } );
			debouncedSpeak(
				__(
					'Showing Reviews by Product block preview.',
					'woocommerce'
				)
			);
		};

		return (
			<Placeholder
				icon={
					<Icon
						icon={ comment }
						className="block-editor-block-icon"
					/>
				}
				label={ __(
					'Reviews by Product',
					'woocommerce'
				) }
				className="wc-block-reviews-by-product"
			>
				{ __(
					'Show reviews of your product to build trust',
					'woocommerce'
				) }
				<div className="wc-block-reviews__selection">
					<ProductControl
						selected={ attributes.productId || 0 }
						onChange={ ( value = [] ) => {
							const id = value[ 0 ] ? value[ 0 ].id : 0;
							setAttributes( { productId: id } );
						} }
						queryArgs={ {
							orderby: 'comment_count',
							order: 'desc',
						} }
						renderItem={ renderProductControlItem }
					/>
					<Button isPrimary onClick={ onDone }>
						{ __( 'Done', 'woocommerce' ) }
					</Button>
				</div>
			</Placeholder>
		);
	};

	if ( ! productId || editMode ) {
		return renderEditMode();
	}

	return (
		<Fragment>
			{ getBlockControls( editMode, setAttributes ) }
			{ getInspectorControls() }
			<EditorContainerBlock
				attributes={ attributes }
				className="wc-block-reviews-by-product"
				icon={
					<Icon
						icon={ comment }
						className="block-editor-block-icon"
					/>
				}
				name={ __(
					'Reviews by Product',
					'woocommerce'
				) }
				noReviewsPlaceholder={ NoReviewsPlaceholder }
			/>
		</Fragment>
	);
};

ReviewsByProductEditor.propTypes = {
	/**
	 * The attributes for this block.
	 */
	attributes: PropTypes.object.isRequired,
	/**
	 * The register block name.
	 */
	name: PropTypes.string.isRequired,
	/**
	 * A callback to update attributes.
	 */
	setAttributes: PropTypes.func.isRequired,
	// from withSpokenMessages
	debouncedSpeak: PropTypes.func.isRequired,
};

export default withSpokenMessages( ReviewsByProductEditor );
