/**
 * External dependencies
 */
import { withA11y } from '@storybook/addon-a11y';
import { withKnobs } from '@storybook/addon-knobs';
import { addDecorator } from '@storybook/react';

/**
 * Internal dependencies
 */

import './wordpress/css/common.min.css';
import './wordpress/css/forms.min.css';
import './wordpress/css/admin-menu.min.css';
import './wordpress/css/dashboard.min.css';
import './wordpress/css/list-tables.min.css';
import './wordpress/css/edit.min.css';
import './wordpress/css/revisions.min.css';
import './wordpress/css/media.min.css';
import './wordpress/css/themes.min.css';
import './wordpress/css/about.min.css';
import './wordpress/css/nav-menus.min.css';
import './wordpress/css/widgets.min.css';
import './wordpress/css/site-icon.min.css';
import './wordpress/css/l10n.min.css';
import './wordpress/css/site-health.min.css';

addDecorator( withA11y );
addDecorator( withKnobs );
addDecorator( ( Story ) => (
	<div id="wpwrap">
		<div className="woocommerce-layout woocommerce-page">
			<Story />
		</div>
	</div>
) );
