/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { Icon, postComments } from '@wordpress/icons';
import {
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanel as ToolsPanel,
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalToolsPanelItem as ToolsPanelItem,
	ToggleControl,
} from '@wordpress/components';

/**
 * Internal dependencies
 */
import EditorContainerBlock from '../editor-container-block';
import NoReviewsPlaceholder from './no-reviews-placeholder';
import {
	getSharedReviewContentControls,
	getSharedReviewListControls,
} from '../edit-utils.js';
import type { AllReviewsEditorProps } from './types';

/**
 * Component to handle edit mode of "All Reviews".
 *
 * @param {Object}            props               Incoming props for the component.
 * @param {Object}            props.attributes    Incoming block attributes.
 * @param {function(any):any} props.setAttributes Setter for block attributes.
 */
const AllReviewsEditor = ( {
	attributes,
	setAttributes,
}: AllReviewsEditorProps ) => {
	const getInspectorControls = () => {
		return (
			<InspectorControls key="inspector">
				<ToolsPanel
					label={ __( 'Content', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							showProductName: true,
							showReviewRating: true,
							showReviewerName: true,
							showReviewImage: true,
							showReviewDate: true,
							showReviewContent: true,
							imageType: 'reviewer',
						} )
					}
				>
					<ToolsPanelItem
						hasValue={ () => ! attributes.showProductName }
						label={ __( 'Product name', 'woocommerce' ) }
						onDeselect={ () =>
							setAttributes( { showProductName: true } )
						}
						isShownByDefault
					>
						<ToggleControl
							__nextHasNoMarginBottom
							label={ __( 'Product name', 'woocommerce' ) }
							checked={ attributes.showProductName }
							onChange={ () =>
								setAttributes( {
									showProductName:
										! attributes.showProductName,
								} )
							}
						/>
					</ToolsPanelItem>
					{ getSharedReviewContentControls(
						attributes,
						setAttributes
					) }
				</ToolsPanel>
				<ToolsPanel
					label={ __( 'List Settings', 'woocommerce' ) }
					resetAll={ () =>
						setAttributes( {
							showOrderby: true,
							orderby: 'most-recent',
							reviewsOnPageLoad: 10,
							showLoadMore: true,
							reviewsOnLoadMore: 10,
						} )
					}
				>
					{ getSharedReviewListControls( attributes, setAttributes ) }
				</ToolsPanel>
			</InspectorControls>
		);
	};

	return (
		<>
			{ getInspectorControls() }
			<EditorContainerBlock
				attributes={ attributes }
				icon={
					<Icon
						icon={ postComments }
						className="block-editor-block-icon"
					/>
				}
				name={ __( 'All Reviews', 'woocommerce' ) }
				noReviewsPlaceholder={ NoReviewsPlaceholder }
			/>
		</>
	);
};

export default AllReviewsEditor;
