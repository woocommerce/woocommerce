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
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __, _x } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	// @ts-expect-error - Experimental component
	// eslint-disable-next-line @wordpress/no-unsafe-wp-apis
	__experimentalDateFormatPicker as DateFormatPicker,
} from '@wordpress/block-editor';

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

	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-expect-error - the type of useEntityProp is not correct
	let [ date ] = useEntityProp( 'root', 'comment', 'date', commentId );
	const [ siteFormat = getDateSettings().formats.date ] = useEntityProp(
		'root',
		'site',
		'date_format'
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
			<div { ...blockProps }>{ reviewDate }</div>
		</>
	);
}
