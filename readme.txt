=== Theatre ===
Contributors: slimndap
Tags: theatre, stage, venue, events, shows, concerts, tickets, ticketing
Requires at least: 3.5
Tested up to: 3.8
Stable tag: trunk
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Add event listings to your Wordpress website. Perfect for theaters, music venues, museums, conference centers and performing artists.

== Description ==
This plugin gives you the ability to manage seasons, productions and events in Wordpress and comes with all necessary shortcodes and widgets to show your events on your website.

Theme developers get three new PHP objects (Season, Production and Event) which they can use to further integrate Theatre into their theme. Each PHP object comes with a wide variety of smart methods and can be extended with your own methods. 

It is also possible to extend the functionality with other popular plug-ins.

__International__
Available in English, French and Dutch.

__Scope__

The Theatre plugin is kept as simple as possible so it can be used for a wide variety of event websites. 

___What is included___

* Admin screens for seasons, productions and events.
* Default templates for productions.
* Short codes for listings of productions and events.
* Widgets for listings of productions and events.

__Contributors Welcome__

* Submit a [pull request on Github](https://github.com/slimndap/wp-theatre)

__Author__

* [Jeroen Schmit, Slim & Dapper](http://slimndap.com)

== Installation ==

1. Look for 'theatre' in the Wordpress plugin directory.
1. Install the Theatre plugin (by Jeroen Schmit, Slim & Dapper).
1. Activate the plugin.

Your Wordpress admin screen now has a new menu-item in the left column: Theatre.

= Managing your events =

Let's add a single event.

Make sure that the _Show events on production page._-option is checked on the _Theatre/Settings_ page.

First create a production:

1. Click on _Theatre/Productions_.
1. Click on _Add new_.
1. Give your production a title, some content and a featured image.
1. Click on _Publish_.
1. Click on _View post_

You are now looking at your first production. It probably looks exactly like any other post or page.

1. Edit the event you just created.
1. In the right column, click on 'New event'.
1. Set the event date, venue and city. Make sure the event date is a date in the future. Optionally, add an URL for the tickets. 
1. Click on _Publish_.
1. Click on the title of your production.
1. Click on _View post_

Your should now see your production with the event details at the bottom!

= Upcoming events =

To add a listing with all upcoming events to your Wordpress website:

1. Create a new blank page (eg. 'Upcoming events').
1. Place `[wp_theatre_events]` in the content.
1. Publish the page and view it.
1. Done!

It is also possible to paginate the listing by month by altering the shortcode a bit:

    [wp_theatre_events paged=1]

*Widgets*

Theatre also comes with two widgets to show your upcoming events in the sidebar:

* Theatre Events: a list of upcoming events. 
* Theatre Productions: a list of productions with upcoming events. 

You can limit the size of the lists in the corresponding widget's settings.

= Production pages =

Production pages look exactly the same as regular post pages. However, you can add a listing of all the events for the current production to the page. 

You have two options:

* Check 'Show events on production page' on the Theatre settings page in the Wordpress admin. The listing is added to the bottom of the content of the production.
* Add the `[wpt_production_events]` shortcode to the content of the production.

= Theme developers =

Check out the [documentation](https://github.com/slimndap/wp-theatre/wiki). 

== Changelog ==

= 0.3.5 =
* Updated Dutch language files
* Improved layout of production listings
* Show tickets pages in an iframe, new window of lightbox/thickbox
* Shopping cart (requires help from a developer)

= 0.3.4 =
* Better in avoiding CSS conflict with themes

= 0.3.3 =
* Microdata for events
* Speed improvements

= 0.3.2 =
* new widget: upcoming productions

= 0.3.1 =
* bugfixes and technical improvements
* better support for bulk-editing productions
* better support for quick-editing productions

= 0.3 =
* bugfix: events with the same date and time were causing conflicts.
* support for my upcoming Ticketmatic extension.

= 0.2.7 =
* 2 extra columns (dates and cities) to productions admin page.
* Grouped and paged event listings.

= 0.2.6 =
* Support for sticky productions.
* Support for French language.

= 0.2.5 =
* Added CSS for shortcodes and widgets.

= 0.2.4 =
* Added a dashboard widget.
* Events can have a remark (eg. try-out, premiere, special guests).
* Added a sidebar widget that lists all upcoming events.

= 0.2.3 =
* Support for sold out events
* Custom text on ticket-buttons

= 0.2.2 =
* Support for Dutch language.

= 0.2.1 =
* Theatre now has it's own admin menu
* New settings page

= 0.2 =
* Several smart functions which can be used inside templates.
* Short code for listing of events.

= 0.1 =
* Basic version of the plugin.


