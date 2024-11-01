=== WPMU LinkManager ===
Contributors: wpler
Donate link: https://www.wpler.com/
Tags: multisite,links,footer
Requires at least: 3.4.2
Tested up to: 5.1
Stable tag: 1.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Manage your Themes footer links for every blog into a multisite includes with conditional tags.

== Description ==

[Deutsche Beschreibung des Plugins](https://www.wpler.com/linkmanager-fur-multisites/)

You run a Multisite Blogsystem with open registration for users to add own blogs and their can using a theme by
different installed themes, but you want to show a link into the footers of the themes but only once time and for
every blog another one?

With this plugin you can manage your outgoing links into the themes by a shortcode/function for every blog. You can
set for every link the target, relation (Attribute rel), linktext and -title and the target url. Also you can set more
than one link for a blog.

For output you can use a shortcode or a function <?php do_shortcode('[wplerlm]' ); ?>. This function get the links
for the current blog-id and printed this, if links are available.

All options/links can set by network admin only. The outputs needs no capability.

== Installation ==

1. Upload the Directory `wpler_linkmanager` to the `/wp-content/plugins/` directory
2. Activate the plugin networkwide through the 'Plugins' menu in WordPress Network-Adminsitration
3. After activation add some links for your blogs into 'LinkManager' menu 'New Link'
4. Place `<?php do_shortcode('[wplerlm]'); ?>` in your templates, where the links should by show.

== Frequently Asked Questions ==

= How can I show a link on the homepage only? =

Use a [Conditional Tag](http://codex.wordpress.org/Conditional_Tags) like `is_home()` if you add the link into the 
textarea field by the form.

= If I using a free theme, can i change the footer links? =

Look if the author are allowed to change it. Someones don't like or not allowed it. If you using a free theme by 
wordpress repositary in most themes you can change the footer links with your own code. Replace this footer links 
with the function `<?php do_shortcode('[wplerlm]'); ?>` and add some links for every blog to show your own links 
on the footer of this theme.

== Screenshots ==

1. Overview of the stored links for all blogs
2. Add a new Link

== Changelog ==

= 1.0.4 =
* Test the current version on WordPress 5.1
* Add a help tab on the link manager screen
* fix a wpdb prepare warning

= 1.0.3 =
* Add a if query for asking if rel & title are not empty, otherwise this attributes will not printed

= 1.0.2 =
* Fix an error by saving new links into the table
* Fix an error by delete a link
* Fix an error for the conditional tags by printout the links

= 1.0.1 =
* Fix an error by activation & deactivation for creating the tables

= 1.0 =
* Initial creation of this project

