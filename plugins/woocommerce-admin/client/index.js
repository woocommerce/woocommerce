/** @format */
/**
 * External dependencies
 */
import '@wordpress/notices';
import { render } from '@wordpress/element';

/**
 * Internal dependencies
 */
import './stylesheets/_index.scss';
import { PageLayout } from './layout';
import 'wc-api/wp-data-store';

render( <PageLayout />, document.getElementById( 'root' ) );
