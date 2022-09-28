/**
 * External dependencies
 */
import classnames from 'classnames';
import { useBlockProps } from '@wordpress/block-editor';
import { Disabled, withSpokenMessages } from '@wordpress/components';
import BlockTitle from '@woocommerce/editor-components/block-title';
import type { BlockEditProps } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';
import { Attributes } from './types';

const Edit = ( {
	attributes,
	setAttributes,
}: BlockEditProps< Attributes > ) => {
	const { className, heading, headingLevel } = attributes;

	const blockProps = useBlockProps( {
		className: classnames( 'wc-block-rating-filter', className ),
	} );

	return (
		<>
			{
				<div { ...blockProps }>
					<BlockTitle
						className="wc-block-rating-filter__title"
						headingLevel={ headingLevel }
						heading={ heading }
						onChange={ ( value: string ) =>
							setAttributes( { heading: value } )
						}
					/>
					<Disabled>
						<Block attributes={ attributes } isEditor={ true } />
					</Disabled>
				</div>
			}
		</>
	);
};

export default withSpokenMessages( Edit );
