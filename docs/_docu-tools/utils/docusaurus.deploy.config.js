const baseConfig = require('../docusaurus.config');

const ASSET_PREFIX = '/wp-content/plugins/docu-loader/docs-build/';

module.exports = {
    ...baseConfig,

    favicon: '/wp-content/plugins/docu-loader/docs-build/img/favicon.ico',
    themeConfig: {
        ...baseConfig.themeConfig,
        navbar: {
            ...baseConfig.themeConfig.navbar,
            logo: {
                ...baseConfig.themeConfig.navbar.logo,
                src: '/wp-content/plugins/docu-loader/docs-build/img/woo-dev-site-logo.svg',
                srcDark: '/wp-content/plugins/docu-loader/docs-build/img/woo-dev-site-logo-dark.svg',
            }
        },
        footer: {
            ...baseConfig.themeConfig.footer,
            copyright: baseConfig.themeConfig.footer.copyright.replace(
                'src="img/automattic.svg"',
                'src="/wp-content/plugins/docu-loader/docs-build/img/automattic.svg"'
            ).replace(
                'src="img/automattic_dark.svg"',
                'src="/wp-content/plugins/docu-loader/docs-build/img/automattic_dark.svg"'
            )
        }
    },
    ssrTemplate: `<!DOCTYPE html>
        <html <%~ it.htmlAttributes %>>
        <head>
            <meta charset="UTF-8">
            <meta name="generator" content="Docusaurus v<%= it.version %>">
            <% it.metaAttributes.forEach((metaAttribute) => { %>
            <%~ metaAttribute %>
            <% }); %>
            <%~ it.headTags %>
            <% it.stylesheets.forEach((stylesheet) => { %>
            <link rel="stylesheet" href="${ASSET_PREFIX}<%= stylesheet %>" />
            <% }); %>
            <% it.scripts.forEach((script) => { %>
            <link rel="preload" href="${ASSET_PREFIX}<%= script %>" as="script">
            <% }); %>
        </head>
        <body <%~ it.bodyAttributes %>>
            <%~ it.preBodyTags %>
            <div id="__docusaurus">
            <%~ it.appHtml %>
            </div>
            <% it.scripts.forEach((script) => { %>
            <script src="${ASSET_PREFIX}<%= script %>"></script>
            <% }); %>
            <%~ it.postBodyTags %>
        </body>
        </html>`,

    plugins: [
        ...(baseConfig.plugins || []).map((plugin) => {
            const path = Array.isArray(plugin) ? plugin[0] : plugin;
            if (typeof path === 'string' && path.startsWith('./')) {
                const newPath = path.replace(/^\.\//, '../');
                return Array.isArray(plugin)
                    ? [newPath, plugin[1]]
                    : newPath;
            }
            return plugin;
        }),
        function pluginAssetPrefix() {
            return {
                name: 'set-deploy-asset-prefix',
                configureWebpack() {
                    return {
                        output: {
                            publicPath: ASSET_PREFIX,
                        },
                    };
                },
            };
        },
    ],
};