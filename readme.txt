=== Xo for Angular ===
Contributors: warriorrocker
Tags: angular, angular cli, angular cms, single page application, routes, dynamic routing, controllers, resolvers, annotations
Requires at least: 4.9
Tested up to: 5.1
Stable tag: 1.1.1
Requires PHP: 7.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Modern Angular development with WordPress.

== Description ==

Xo provides a foundation for both Angular and WordPress developers to use what they know best.

1. Use WordPress to manage content for pages and posts and let Xo generate your routes dynamically.
2. Use the Xo API to get pages, posts, menus, options, and more in your Angular app.
3. Run locally using ng serve for rapid development of new features and updates.
4. Deploy for production using ng build and distribute your theme with or without the source code.

Getting started with Xo for Angular is easy, find the quick start guides [here](https://angularxo.io/guides).

Xo for Angular also provides an extensive customization API provided [here](https://angularxo.io/api). 

== Installation ==

1. Install the [angular-xo](https://wordpress.org/plugins/angular-xo/) plugin to WordPress.
2. Add the [angular-xo](https://www.npmjs.com/package/angular-xo) module to your Angular app.
3. Build something amazing!

Check out the full docs [here](https://angularxo.io) for tips on adding [Template Annotations](https://angularxo.io/guides/templates), [Dynamic Routing](https://angularxo.io/guides/routing), [Resolvers](https://angularxo.io/guides/angular#post-resolvers), and much more!

== Changelog ==

= 1.1.1 =
* Fix issue preventing plugin activation.

= 1.1.0 =
* Add new breadcrumbs API endpoint.
* General cleanup of services and classes.

= 1.0.10 =
* Remove errant echo in templates output.

= 1.0.9 =
* Fix issue preventing new pages from saving.
* Add new API endpoint to retrieve a sitemap of posts and terms.

= 1.0.8 =
* Update menus API endpoint and interfaces.

= 1.0.7 =
* Add new terms get API endpoint.

= 1.0.6 =
* Update admin option descriptions.

= 1.0.5 =
* Minor deployment fix.

= 1.0.4 =
* Add new API endpoint to retrieve post type config.
* Update styles used in admin options.

= 1.0.3 =
* Fix routes output for posts without rewrite base.

= 1.0.2 =
* Fix issue with SVG icon loading in admin options.

= 1.0.1 =
* Added link target to menu item output.
* Fix for posts filter API returning results when there is no match.

= 1.0.0 =
* Xo for Angular initial release.
