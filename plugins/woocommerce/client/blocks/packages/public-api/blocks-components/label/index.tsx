/**
 * External dependencies
 */
import { Fragment, RawHTML } from '@wordpress/element';
import { sanitizeHTML } from '@woocommerce/sanitize';
import clsx from 'clsx';
import type { ReactElement, HTMLProps } from 'react';

export interface LabelProps extends HTMLProps< HTMLElement > {
	label?: string | undefined;
	allowHTML?: boolean | undefined;
	screenReaderLabel?: string | undefined;
	wrapperElement?: string | undefined;
	wrapperProps?: HTMLProps< HTMLElement > | undefined;
}

/**
 * Component used to render an accessible text given a label and/or a
 * screenReaderLabel. The wrapper element and wrapper props can also be
 * specified via props.
 *
 */
const Label = ( {
	label,
	screenReaderLabel,
	wrapperElement,
	wrapperProps = {},
	allowHTML = false,
}: LabelProps ): ReactElement => {
	let Wrapper;

	const hasLabel = typeof label !== 'undefined' && label !== null;
	const hasScreenReaderLabel =
		typeof screenReaderLabel !== 'undefined' && screenReaderLabel !== null;

	if ( ! hasLabel && hasScreenReaderLabel ) {
		Wrapper = wrapperElement || 'span';
		wrapperProps = {
			...wrapperProps,
			className: clsx( wrapperProps.className, 'screen-reader-text' ),
		};

		return <Wrapper { ...wrapperProps }>{ screenReaderLabel }</Wrapper>;
	}

	Wrapper = wrapperElement || Fragment;

	if ( hasLabel && hasScreenReaderLabel && label !== screenReaderLabel ) {
		return (
			<Wrapper { ...wrapperProps }>
				{ allowHTML ? (
					<RawHTML>
						{ sanitizeHTML( label, {
							tags: [
								'b',
								'em',
								'i',
								'strong',
								'p',
								'br',
								'span',
							],
							attr: [ 'style' ],
						} ) }
					</RawHTML>
				) : (
					<span aria-hidden="true">{ label }</span>
				) }

				<span className="screen-reader-text">
					{ screenReaderLabel }
				</span>
			</Wrapper>
		);
	}

	return <Wrapper { ...wrapperProps }>{ label }</Wrapper>;
};

export default Label;
