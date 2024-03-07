/**
 * External dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { withSpokenMessages } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { EditProps } from './types';
import { Inspector } from './components/inspector-controls';
import './style.scss';

const Edit = ( props: EditProps ) => {
	return (
		<div { ...useBlockProps() }>
			<Inspector { ...props } />
			Taxonomy filter
		</div>
	);
};

export default withSpokenMessages( Edit );
