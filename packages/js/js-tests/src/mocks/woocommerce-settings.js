module.exports = {
	getSetting: ( key, backup ) => {
		return global.wcSettings[ key ] || backup;
	},
	getAdminLink: ( path ) => {
		if ( global.wcSettings && global.wcSettings.adminUrl ) {
			return global.wcSettings.adminUrl + path;
		}
		return path;
	},
};
