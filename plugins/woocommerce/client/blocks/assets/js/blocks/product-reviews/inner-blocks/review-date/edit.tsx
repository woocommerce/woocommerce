/**
 * External dependencies
 */
import { useEntityProp } from '@wordpress/core-data';
import type { BlockEditProps } from '@wordpress/blocks';
import {
	dateI18n,
	humanTimeDiff,
	getSettings as getDateSettings,
} from '@wordpress/date';
import {
	InspectorControls,
	useBlockProps,
	// @ts-expect-error - Experimental component
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalDateFormatPicker as DateFormatPicker,
} from '@wordpress/block-editor';
// Fix dependency group error
// eslint-disable-next-line @woocommerce/dependency-group
import { PanelBody, ToggleControl } from '@wordpress/components';
// eslint-disable-next-line @woocommerce/dependency-group
import { __, _x } from '@wordpress/i18n';

/**
 * Renders the `woocommerce/product-review-date` block on the editor.
 *
 * @param {Object} props                   React props.
 * @param {Object} props.setAttributes     Callback for updating block attributes.
 * @param {Object} props.attributes        Block attributes.
 * @param {string} props.attributes.format Format of the date.
 * @param {string} props.attributes.isLink Whether the author name should be linked.
 * @param {Object} props.context           Inherited context.
 * @param {string} props.context.commentId The comment ID.
 *
 * @return {JSX.Element} React element.
 */
export default function Edit( {
	attributes: { format, isLink },
	context: { commentId },
	setAttributes,
}: BlockEditProps< {
	format: string;
	isLink: boolean;
} > & {
	context: { commentId: number };
} ) {
	const blockProps = useBlockProps();
	let [ date ] = useEntityProp(
		'root',
		'comment',
		'date',
		String( commentId )
	);
	const [ siteFormat = getDateSettings().formats.date ] = useEntityProp(
		'root',
		'site',
		'date_format'
	);

	const inspectorControls = (
		<InspectorControls>
			<PanelBody title={ __( 'Settings', 'woocommerce' ) }>
				<DateFormatPicker
					format={ format }
					defaultFormat={ siteFormat }
					onChange={ ( nextFormat: string ) =>
						setAttributes( { format: nextFormat } )
					}
				/>
				<ToggleControl
					__nextHasNoMarginBottom
					label={ __( 'Link to review', 'woocommerce' ) }
					onChange={ () => setAttributes( { isLink: ! isLink } ) }
					checked={ isLink }
				/>
			</PanelBody>
		</InspectorControls>
	);

	if ( ! commentId || ! date ) {
		date = _x( 'Review Date', 'block title', 'woocommerce' );
	}

	let reviewDate =
		date instanceof Date ? (
			<time dateTime={ dateI18n( 'c', date, true ) }>
				{ format === 'human-diff'
					? humanTimeDiff( date, new Date() )
					: dateI18n( format || siteFormat, date, true ) }
			</time>
		) : (
			<time>{ date }</time>
		);

	if ( isLink ) {
		reviewDate = (
			<a
				href="#review-date-pseudo-link"
				onClick={ ( event ) => event.preventDefault() }
			>
				{ reviewDate }
			</a>
		);
	}

	return (
		<>
			{ inspectorControls }
			<div { ...blockProps }>{ reviewDate }</div>
		</>
	);
}
