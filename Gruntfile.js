module.exports = function(grunt){

    grunt.initConfig({
        // Compile specified less files
        less: {
            compile: {
                options: {
                    // These paths are searched for @imports
                    paths: ["assets/css"]
                },
                files: {
                    "assets/css/woocommerce.css": "assets/css/woocommerce.less"
                }
            }
        },

        cssmin: {
            "assets/css/woocommerce.css": ["assets/css/woocommerce.css"]
        },

    });

    // Load NPM tasks to be used here
    grunt.loadNpmTasks("grunt-contrib-less");
    grunt.loadNpmTasks("grunt-contrib-cssmin");

    // Register tasks
    grunt.registerTask( 'default', []);
    grunt.registerTask( 'dev', ["less:compile", "cssmin"]);

};