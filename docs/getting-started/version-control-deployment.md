---
post_title: Version control and deployment
sidebar_label: Version control and deployment
sidebar_position: 8
---

# Version control and deployment

Because WordPress and WooCommerce are both developer frameworks *and* content management systems, it’s important to plan ahead for how you will version control your code and deploy changes to a live environment. A common rule is that “code goes up, content goes down,” meaning that you’ll push your custom code up from a local or staging environment, but never the contents of your database or user-generated content that lives in the `wp-content/uploads` directory.

## Considerations

There is no one-size-fits-all approach to deploying WordPress. Your hosting environment may have a dedicated approach that you need to follow, especially at more enterprise-level hosts like [WordPress VIP](https://docs.wpvip.com/development-workflow/). 

Based on the [project structure](/docs/getting-started/project-structure) of a WordPress installation and your unique needs, you may be version controlling and deploying different parts of your `wp-content` directory. Some common approaches include:

1. Version control starts from the root directory of WordPress, but includes a `.gitignore` file that ignores most of WordPress core and plugins, if your hosting company manages WordPress updates for you. An example `.gitignore` file is available [here from GitHub](https://github.com/github/gitignore/blob/main/WordPress.gitignore) and you can update it to ensure that your custom code is version controlled. This approach is more popular with builders who are managing each site in its own repository.   
2. Version control just the directory that you are working in, typically in `wp-content/plugins/your-extension` or `wp-content/themes/your-theme`. This approach is more common for extension and theme developers who are broadly distributing their code across multiple sites.

**Note:** Do not commit your `wp-config.php` file to a public repository. It may include sensitive information and most likely it will have differences from your local and production environments. 

## Building and Deployment

Most WordPress hosting environments *do not have support* for Node or Composer. You’ll need to confirm with the environment that you’re deploying to, but keep this in mind when setting up your workflow. Some popular approaches include:

1. Committing built files to your repository. Plugins and themes that come from the WordPress.org directory, for example, include all built assets for easy updates.  
2. Adding a build step via a tool like GitHub Actions or a separate CI/CD pipeline.
