/**
 * External dependencies
 */
import classNames from 'classnames';
import { createElement } from '@wordpress/element';
import {
	// @ts-expect-error no exported member.
	useInnerBlocksProps,
} from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import { SectionBlockAttributes } from './types';
import { ProductEditorBlockEditProps } from '../../../types';
import { SectionHeader } from '../../../components/section-header';

export function SectionBlockEdit( {
	attributes,
	// @ts-ignore
	children,
}: ProductEditorBlockEditProps< SectionBlockAttributes > ) {
	const { description, title } = attributes;

	const SectionTagName = title ? 'fieldset' : 'div';

	return (
		<SectionTagName>
			{ title && (
				<SectionHeader
					description={ description }
					sectionTagName={ SectionTagName }
					title={ title }
				/>
			) }
			{ children }
		</SectionTagName>
	);
}
