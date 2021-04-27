export default [
	{
		command: 'Trigger WCA Install',
		description: `This will trigger a WooCommerce Admin install, which usually
    happens when a new version (or new install) of WooCommerce
    Admin is installed. Triggering the install manually can
    run tasks such as removing obsolete admin notes.`,
		action: 'triggerWcaInstall',
	},
];
