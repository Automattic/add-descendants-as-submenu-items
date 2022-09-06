=== Add Descendants As Submenu Items ===
Contributors: Viper007Bond
Tags: menu, nav menu, children, descendants
Tested up to: 6.0
Stable tag: 1.2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically all of a nav menu item's descendants as submenu items. Designed for pages but will work with any hierarchical post type or taxonomy.

== Description ==

When adding a page, category, or any hierarchical custom post type or taxonomy to your navigation menu (Appearance &rarr; Menus), this plugin allows you to optionally automatically add all descendants (children) of that menu item as submenu items.

In short, you'll no longer have to manually maintain submenus when you add a new child page to your site.

== Installation ==

Visit Plugins &rarr; Add New in your administration area and search for the name of this plugin.

== Screenshots ==

1. The new checkbox that this plugin adds.
2. The child pages have automatically been added as submenu items.

== ChangeLog ==

= Version 1.2.2 =
* Fix fatal error in customizer when using taxonomies in menus.
* Add POT file for translations.

= Version 1.2.1 =
* Minor update allowing for loading translation files from WordPress.org.

= Version 1.2.0 =
* `_get_post_ancestors()` will/was deprecated in WordPress 3.5 and no longer works. Parts of this plugin have been rewritten to more properly get post ancestors.
* Bug fix: Don't highlight parents of different types. Post types and terms can have the same IDs.

= Version 1.1.0 =
* Support for hierarchical taxonomies (i.e. categories). Props WPAddiction for the idea.
* Translatable plugin headers.

= Version 1.0.1 =
* Fix sorting by secondarily sorting by title.

= Version 1.0.0 =
* Initial release!