/**
 * External dependencies.
 */
import { TabPanel } from '@wordpress/components';

/**
 * Internal dependencies
 */
// TODO replace this with the actual controls
// import { Options } from '../Options';
const Options = () => <h2>Options</h2>;
const AdminNotes = () => <h2>Admin notes</h2>;

const tabs = [
    {
        name: 'options',
        title: 'Options',
        content: <Options/>,
    },
    {
        name: 'admin-notes',
        title: 'Admin notes',
        content: <AdminNotes/>
    },
];

export function App() {
    return (
        <div className="wrap">
            <h1>WooCommerce Admin Test Helper</h1>
            <TabPanel
                className="woocommerce-admin-test-helper__main-tab-panel"
                activeClass="active-tab"
                tabs={ tabs }
                initialTabName={ tabs[0].name }
            >
                { ( tab ) => <p>{ tab.content }</p> }
            </TabPanel>
        </div>
    );
}
