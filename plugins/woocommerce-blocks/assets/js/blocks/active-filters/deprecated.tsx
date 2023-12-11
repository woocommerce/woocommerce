/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import { blockAttributes } from './attributes';
import metadata from './block.json';
import { Attributes } from './types';

const v1 = {
	attributes: {
		...metadata.attributes,
		...blockAttributes,
	},
	save: ( { attributes }: { attributes: Attributes } ) => {
		const { className, displayStyle, heading, headingLevel } = attributes;
		const data = {
			'data-display-style': displayStyle,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};

		return (
			<div
				{ ...useBlockProps.save( {
					className: classNames( 'is-loading', className ),
				} ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-active-filters__placeholder"
				/>
			</div>
		);
	},
};

const deprecated = [ v1 ];

export default deprecated;
