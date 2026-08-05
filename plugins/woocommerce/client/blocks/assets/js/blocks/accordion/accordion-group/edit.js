/**
 * External dependencies
 */
import {
	useBlockProps,
	useInnerBlocksProps,
	InspectorControls,
	Warning,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { PanelBody, ToggleControl, Button } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { createBlock } from '@wordpress/blocks';
import { isWpVersion } from '@woocommerce/settings';

const ACCORDION_BLOCK_NAME = 'woocommerce/accordion-item';
const ACCORDION_BLOCK = {
	name: ACCORDION_BLOCK_NAME,
};

/**
 * Deprecation notice component for the WooCommerce Accordion block.
 *
 * @param {Object} props          - Component props.
 * @param {string} props.clientId - The block client ID.
 *
 * @return {JSX.Element} The deprecation notice component.
 */
function DeprecatedBlockEdit( { clientId } ) {
	const { replaceBlocks } = useDispatch( blockEditorStore );

	const { currentBlockAttributes, innerBlocks } = useSelect(
		( select ) => {
			const blockEditor = select( blockEditorStore );
			return {
				currentBlockAttributes:
					blockEditor.getBlockAttributes( clientId ),
				innerBlocks: blockEditor.getBlocks( clientId ),
			};
		},
		[ clientId ]
	);

	/**
	 * Recursively convert WooCommerce accordion blocks to WordPress core accordion blocks.
	 *
	 * @param {Array<*>} blocks - The inner blocks to convert.
	 *
	 * @return {Array<*>} The converted blocks.
	 */
	const convertInnerBlocks = ( blocks ) => {
		// Define attributes to REMOVE for each block type.
		const attributesToRemove = {
			'woocommerce/accordion-header': [
				'icon',
				'textAlignment',
				'levelOptions',
			],
			'woocommerce/accordion-panel': [
				'allowedBlocks',
				'isSelected',
				'openByDefault',
			],
		};

		return blocks.map( ( block ) => {
			let newBlockName = block.name;
			const newAttributes = { ...block.attributes };

			// Map WooCommerce block names to WordPress core block names.
			if ( block.name === 'woocommerce/accordion-item' ) {
				newBlockName = 'core/accordion-item';
				// No attribute changes needed.
			} else if ( block.name === 'woocommerce/accordion-header' ) {
				newBlockName = 'core/accordion-heading';

				// Convert icon to showIcon.
				if ( block.attributes.icon !== undefined ) {
					newAttributes.showIcon = block.attributes.icon !== false;
				}

				// Remove incompatible attributes.
				const headerAttrs =
					attributesToRemove[ 'woocommerce/accordion-header' ];
				headerAttrs.forEach( ( attr ) => {
					delete newAttributes[ attr ];
				} );
			} else if ( block.name === 'woocommerce/accordion-panel' ) {
				newBlockName = 'core/accordion-panel';

				// Remove incompatible attributes.
				const panelAttrs =
					attributesToRemove[ 'woocommerce/accordion-panel' ];
				panelAttrs.forEach( ( attr ) => {
					delete newAttributes[ attr ];
				} );
			}

			// Recursively convert inner blocks.
			const convertedInnerBlocks = block.innerBlocks?.length
				? convertInnerBlocks( block.innerBlocks )
				: [];

			return createBlock(
				newBlockName,
				newAttributes,
				convertedInnerBlocks
			);
		} );
	};

	const updateBlock = () => {
		if ( ! currentBlockAttributes ) {
			return;
		}

		const convertedInnerBlocks = convertInnerBlocks( innerBlocks );

		// Filter accordion-group attributes - remove 'allowedBlocks'.
		const { allowedBlocks, ...filteredGroupAttributes } =
			currentBlockAttributes;

		replaceBlocks(
			clientId,
			createBlock(
				'core/accordion',
				filteredGroupAttributes,
				convertedInnerBlocks
			)
		);
	};

	const actions = [
		<Button key="update" onClick={ updateBlock } variant="primary">
			{ __( 'Upgrade Block', 'woocommerce' ) }
		</Button>,
	];

	return (
		<Warning actions={ actions } className="wc-block-components-actions">
			{ __(
				'This version of the Accordion block is outdated. Upgrade to continue using.',
				'woocommerce'
			) }
		</Warning>
	);
}

/**
 * Edit component for the WooCommerce Accordion Group block.
 *
 * @param {Object}   props                      - Component props.
 * @param {Object}   props.attributes           - Block attributes.
 * @param {boolean}  props.attributes.autoclose - Whether to auto-close other accordions.
 * @param {Function} props.setAttributes        - Function to set block attributes.
 * @param {string}   props.clientId             - The block client ID.
 *
 * @return {JSX.Element} The edit component.
 */
export default function Edit( {
	attributes: { autoclose },
	setAttributes,
	clientId,
} ) {
	const blockProps = useBlockProps();

	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: [ [ ACCORDION_BLOCK_NAME ], [ ACCORDION_BLOCK_NAME ] ],
		defaultBlock: ACCORDION_BLOCK,
		directInsert: true,
	} );

	// Show deprecation notice for WordPress 6.9+.
	if ( isWpVersion( '6.9', '>=' ) ) {
		return <DeprecatedBlockEdit clientId={ clientId } />;
	}

	// Original edit UI for WordPress 6.8 and below.
	return (
		<>
			<InspectorControls key="setting">
				<PanelBody
					title={ __( 'Settings', 'woocommerce' ) }
					initialOpen
				>
					<ToggleControl
						isBlock
						__nextHasNoMarginBottom
						label={ __( 'Auto-close', 'woocommerce' ) }
						onChange={ ( value ) => {
							setAttributes( {
								autoclose: value,
							} );
						} }
						checked={ autoclose }
						help={ __(
							'Automatically close accordions when a new one is opened.',
							'woocommerce'
						) }
					/>
				</PanelBody>
			</InspectorControls>
			<div { ...innerBlocksProps } />
		</>
	);
}
