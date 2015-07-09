=== LeadOut ===
Contributors: andygcook, nelsonjoyce
Tags:  crm, contacts, lead tracking, click tracking, visitor tracking, analytics, marketing automation, inbound marketing, subscription, marketing, lead generation, mailchimp, constant contact, newsletter, popup, popover, email list, email, contacts database, contact form, forms, form widget, popup form
Requires at least: 3.7
Tested up to: 4.2.2
Stable tag: 3.1.8

LeadOut is an easy-to-use marketing automation and lead tracking plugin for WordPress that helps you better understand your web site visitors.

== Description ==

= Get to know your website visitors =

LeadOut is an easy-to-use marketing automation and lead tracking plugin for WordPress that helps you better understand your web site visitors.

[youtube https://www.youtube.com/watch?v=tcMYv2r3ecg]

= Find out who's on your site and what they're doing =
When someone visits your site, you want to know more about them. What pages they've visited, when they return, and what social networks they’re on. LeadOut gives you the details you need to make your next move.

= More context for your conversations =
LeadOut automatically finds publicly available information about each of your contacts. Details such as location, work history, and company info can give you more context when you reach out.

= Convert more visitors to contacts =
Use the optional popup form to prevent people from slipping through the cracks. The popup also uses the contact data to intelligently know when to appear.

= Keep your contacts in sync with your email tool =
LeadOut syncs your contacts to an email list of your choice without replacing any forms.

= Find out what content and traffic sources convert the best =
Our simple analytics show you what sources of traffic and content are driving the most contacts. No more complicated Google Analytics reports.

= How does it work? =

1. When you activate the WordPress plugin, LeadOut will track each anonymous visitor to your site with a cookie.
2. LeadOut automatically identifies and watches each existing form on your site for submissions.
3. Once someone fills out any other form on your site, LeadOut will identify that person with their email address. and add them to your contact list.
4. You'll also receive an email with a link to the new contact record with all of their visit history. (check the screenshots sections to see it in action)

= Who's using LeadOut? =

**<a href="http://www.extremeinbound.com/leadout-wordpress-crm-inbound-plugin/" target="_blank">Alan Perlman</a>**: *“I can use LeadOut to get a sense of how engaged certain contacts are, and I can learn more about their behavior on my website to better drive the conversation and understand what they’re interested in or looking for.”*

**<a href="http://thewpvalet.com/wordpress-lead-tracking/" target="_blank">Adam W. Warner</a>**: *“…the LeadOut plugin has been very useful so far in giving us an idea of the actual visitor paths to our contact forms vs. the paths we’ve intended.”*

== Installation ==

1. Upload the 'leadout' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add an email address under 'LeadOut' in your settings panel

== Frequently Asked Questions ==

How does LeadIn work?

When you activate the WordPress plugin, LeadIn will track each anonymous visitor to your site with a cookie.
Once someone fills out any form on your site, LeadIn will identify that person with their email address.
You’ll receive an email with a link to the new contact record with all of their visit history.
How do I integrate LeadOut with my form plugin?

LeadOut automatically integrates with your contact and comment forms that contain an email address field on your web site. There’s no setup required.

Does LeadOut work with my form builder?

As long as your form builder creates static HTML forms not contained in an iFrame, LeadOut should be able to see the form submissions. Below is a list of all the form builders we have tested.

Some common forms problems:

Must be enclosed in <form> tags
Must not be submitted through AJAX
Must not have any javascript events bound to form submission or button click
Tested + supported:

Contact Form 7
JetPack
Fast Secure Contact Form
Contact Form
Gravity Forms
Formidable
Ninja Forms
Contact Form Clean and Simple
HubSpot
Quform
Native WordPress comment forms
Most custom forms
Tested + unsupported:

Wufoo
WooCommerce
Easy Contact Forms
Disqus comments
Jetpack comment forms
JotForm
SumoMe
Ninja Popups
Forms contained in an iFrame
FormCraft
Why is the popup form not showing up?

There’s a few reasons why you might not be seeing the popup. If you close the popup, LeadOut won’t show it to you again. We recommend trying to view your site in a Chrome ‘incognito window’. You can also use the “preview popup” button in your LeadOut settings to view the popup even if you’ve closed it.

If it’s still not working, the page templates you’re using might not be using standard WordPress header and footer includes. LeadOut requires that your templates have both WordPress header and footer includes to work properly. We include code in those files to make the LeadOut tracking and popups work!

Can I change the style of the popup form?

Right now, there’s no built in ability to change the style of the popup form. You can apply any CSS styles you want to the plugin to make it look how you want. The container class is “.vex-dialog-form”. We may add in basic color changing capabilities into the settings, but it probably wouldn’t be for a while.

What cookies does LeadOut use?

LeadOut starts tracking visitors anonymously using 3 First-Party cookies as soon as you activate the plugin. One cookie stores a unique identifier which doesn’t expire, another stores the last visit and expires after an hour, and one stores whether or not someone is subscribed to the site yet, which lasts two weeks. The plugin tracks where each visitor comes from and what pages they viewed. You own 100% of your data and we don’t have access to it.

Where are my contacts stored?

LeadOut creates a new contact in your Contacts Table whenever an email address is detected in your visitor’s form submission. There is no limit to the number of contacts you can store in your Contacts Table.

What languages is LeadOut available in?

Right now, LeadOut is only available in English. We’re hoping to offer translations soon.

Does LeadOut work with WooCommerce?

Unfortunately, due to the way WooCommerce submits forms, LeadOut is unable to provide tracking for those forms. However, if a visitor submits a supported form on your site, LeadOut will be able to track them on WooCommerce product pages.

Does LeadOut work with multisite?

You betcha! LeadOut should work just fine on multisite right out-of-the-box without requiring any additional setup.

How much does LeadOut cost?

The basic version of LeadOut is completely free! We’re working on some additional features that we’ll change for in the future, but right now everything is free.

== Screenshots ==

1. See the visit history of each contact.
2. Get an email notification for every new lead.
3. LeadOut stats show you where your leads are coming from.
4. Segment your contact list based on page views and submissions.
5. Collect more contacts with the pop-up subscribe widget.
6. Create custom tagged lists, choose the form triggers to add contacts and sync your contacts to third-party email services

== Changelog ==

- Current version: 3.1.9
- Current version release: 2015-05-26

= 3.1.9 (2015.05.26) =

- Bug fixes
- Removed the check on the front-end for missing databases - that was a bad idea - fixed now
- Removed the file_get_contents hack for SVG support and just used an image instead
- Added in support for "phone number" field name to push phone number to ESPs

= 3.1.8 (2015.05.15) =

- Added email connector sync to onboarding
- Sources now check the UTM tags on the first page view visit
- Privacy policy added to plugin 

- Bug fixes
- Fixed dismiss button conflict on WordPress notifications
- Icon styles no longer conflict with other plugins
- Deleted contacts no longer show up in the dashboard
- Popup sync now looks at the actual inputs instead of the field names so it’ll work in other languages
- Popup labels now work in IE
- Fixed bug with SendGrid email delivery for LeadOut Pro

= 3.1.7 (2015.04.15) =
= Enhancements =
- Added debug mode
- Default subscribe confirmation to off

- Bug fixes
- Fixed overly large LeadOut icon in admin bar on front end for logged in users

= 3.1.6 (2015.03.31) =
= Enhancements =
- Show name on contact timeline instead of email address if available
- New contact timeline page styles
- Ability to change color in popup
- Popup now works on mobile

- Bug fixes
- Fixed dashicons not loading in < WP 3.7
- Completely fix all the default object warnings for the contact lookups
- Swap in non-svg logo if allow_url_fopen not toggled on in php.ini
- Fixed Pro email delivery bugs

= 3.1.5 (2015.03.20) =
- Bug fixes
- Changed out call to __DIR__ magic constant which wasn’t implemented until PHP 5.3 in favor of dirname(__FILE__)

= 3.1.4 (2015.03.17) =
= Enhancements =
- Intercom added to plugin for in-app support
- Onboarding improved for non-setup installs
- Contact notifications are now sent through email delivery service to improve deliverability

- Bug fixes
- Namespaced AWeber oauth libraries with LI_ prefix to avoid duplicate class warnings
- SVG icon permanently fixed for servers that don't natively support that file type
- Check if database options are set for subscribe preview button
- Added in check for default leadin_options in case they were deleted and recreate them if they are not there
- Add in checks for the contact lookups to account for default object warnings
- 

= 3.1.3 (2015.02.19) =
= Enhancements =
- Don't show the "You should receive a confirmation email shortly" message in the popup thank you if the confirmation email setting is toggled off

- Bug fixes
- Fixed SVG icon
- Fixed the default object warnings in class-leadout-contact for the enrichment lookups
- Tested NinjaPopups and added to readme as unsupported form plugin
- "Namespace" AWeber with "LI_" prefix to avoid conflicts

= 3.1.2 (2015.01.26) =
= Enhancements =
- Tested JotForm + added compatibility to the ReadMe file

- Bug fixes
- Add in support for like_escape for < WordPress 4.0
- Add first + last names to bulk MailChimp connector
- Remove rogue WPDB prepare in tag list table
- Check for existence of ESP connector when pushing to an email list
- Bug fix for multisite installs with broken onboarding

= 3.1.1 (2014.01.20) =
= Enhancements =
- Added ability to toggle LeadOut data access by user role
- Hide LeadOut nav menu item for user roles without access to LeadOut data 
- Discontinued and disabled the beta program

- Bug fixes
- Fixed broken onboarding in WordPress Multisite after adding a new site to the network
- Contact totals in tag editor now link to tagged list

= 3.1.0 (2015.1.06) =
= Enhancements =
- GetResponse, Campaign Monitor and AWeber integrations launched

= 3.0.0 (2014.12.10) =
= Enhancements =
- Jumping to version 3.0.0 to indefinitely override repository version of LeadOut

= LeadOut 2.2.7 - 2.2.11 =

*LeadOut was split into LeadOut and <a href="http://leadin.com/pro-upgrade" target="_blank">LeadOut Pro</a> after version 2.2.6 and later merged back together, so versions 2.2.7 - 2.2.11 and 3.0.0 - 3.1.3 share similar updates.*

= 2.2.11 (2015.02.18) =

= Enhancements =
- Don't show the "You should receive a confirmation email shortly" message in the popup thank you if the confirmation email setting is toggled off

- Bug fixes
- Fixed SVG icon
- Fixed the default object warnings in class-leadout-contact for the enrichment lookups
- Tested NinjaPopups and added to readme as unsupported form plugin


= 2.2.10 (2015.01.26) =
= Enhancements =
- Tested JotForm + added compatibility to the ReadMe file

- Bug fixes
- Add in support for like_escape for < WordPress 4.0
- Add first + last names to bulk MailChimp connector
- Remove rogue WPDB prepare in tag list table
- Check for existence of ESP connector when pushing to an email list
- Bug fix for multisite installs with broken onboarding

= 2.2.9 (2014.01.20) =
= Enhancements =
- Added ability to toggle LeadOut data access by user role
- Hide LeadOut nav menu item for user roles without access to LeadOut data 
- Discountinued and disabled the beta program

- Bug fixes
- Fixed broken onboarding in WordPress Multisite after adding a new site to the network
- Contact totals in tag editor now link to tagged list

= 2.2.8 (2014.12.15) =
= Enhancements =
- Added in CTAs for LeadOut Pro

= 2.2.7 (2014.12.09) =
- Bug fixes
- Fixing upgrade process from 2.2.6

= 2.2.6 (2014.12.08) =
= Enhancements =
- Added names to contact export
- Added “tagged as” to contact notification email subject lines

- Bug fixes
- Fixed bug with non-tagged contacts being added to tagged lists

= 2.2.6 (2014.12.08) =
= Enhancements =
- Contact Lookup power-up
- Added names to contact exports
- Added “tagged as” to the email subject lines

- Bug fixes
- Fixed bug where LeadOut would add non-tagged emails to ESP lists when it was not supposed to do those contacts 

= 2.2.5 (2014.11.20) =
- Bug fixes
- Fixes to bulk action labels
- Fixed Add Tag button

= 2.2.4 (2014.10.31) =
- Bug fixes
- Patch for 2.2.3 database structure. We forgot to include the new form_hashkey field in the database upgrade

= 2.2.3 (2014.10.31) =
= Enhancements =
- Added "Tags" link to sidebar menu
- Added the applied tags on form submission timeline events
- Added the form selector on submission events in the timeline
- Added language in the subject of the contact notification emails to indicate returning vs. new visitors
- LeadOut will now detect first names + last names and store them on the contact + push to ESP connectors
- Retroactively apply names to all contacts where possible

- Bug fixes
- If a contact changes their email, LeadOut will now push the new email to the ESP connectors
- Added safeguards into all third party libraries to see if they are included already in the WordPress admin
- Added default Javascript values to the popup form if the get_footer function isn't being called

= 2.2.2 (2014.10.16) =
= Enhancements =
- LeadOut now include the utm_ tags from the original first page view when parsing the sources

- Bug fixes
- Unchecking all the template checkboxes for the popup then saving no longer rechecks them all
- Added in current_time fix for older versions of WordPress
- Retooled tag editor to only pull down unique selectors
- Contact list now will go back to the previous page when clicking the back link
- Fixed mysterious bug where popup ignored new visitors
- NOW the subscription confirmation stays checked/unchecked on save (Thanks Kate!)

= 2.2.1 (2014.10.01) =
= Enhancements =
- Added video from WPApplied to readme file

- Bug fixes
- Page view filters now work in the all contacts list
- Subscription confirmation box didn't work in settings page if the "homepage" checkbox was unchecked
- LeadOut menu link no longer shows up in the front-end menu bar for non-logged in users
- Stopped selecting duplicate tags on a contact in the timeline view
- Select inputs did not pull down the text and instead used the value. Fixed and use text now for selected option
- Timezones with a database offset on the contact timeline were not correctly fixed in last update
- Fix to ignore all cURL calls if script isn't present on the server
- Disable beta program is cURL does not exist on the server
- Fixed “<- All contacts” link showing up next to back link on a specific contact type in timeline view

= 2.2.0 (2014.09.25) =
= Enhancements =
- Added ability to ignore logged in user roles from tracking
- Popup can be previewed on the front end site before saving changes
- MailChimp Connect checks for faulty API keys and prompts the user to enter in one that works on the tag editor page
- Email headers for contact notificaitons come from the person who filled in the form
- Added traffic source URL parameters to contact notification emails

- Bug fixes
- LeadOut now accounts for timezones discrepancy on some MySQL databases and offsets to local time
- Filters are now persistent when clicking the link back to the contact list from a contact timeline
- cURL dependency no longer prints the raw error to the screen on installation and gracefully disables cURL-dependant features
- Stats page and contact list totals didn't match up - fixed

= 2.1.0 (2014.09.19) =
= Enhancements =
- Improved onboarding
- Added setting include a short description to the popup under the form heading
- General style improvements to the popup form power-up

- Bug fixes
- Contact filters are now persistent when navigating back to the main contact list from the contact timeline

= 2.0.2 (2014.09.09) =

- Bug fixes
- Fix inconsistent sources on stats widgets and contact timeline widgets
- Onboarding tooltip popup for setting up settings now works correctly
- Parse out get vars for traffic sources in the contact timeline

= 2.0.1 (2014.09.01) =
= Enhancements =
- Removed "Who read my post" widget analytics from the post editor
- Separated backend from frontend code to speed up ajax calls on both sides

- Bug fixes
- Fixed bug when deleting specifically selected contacts looked like all the contacts were deleted on the page refresh
- Organic traffic and paid traffic sources are now parsing more accurately
- Credit card forms will add to the timeline now but will block all credit card information
- Bulk edited tags now push contacts to ESP lists when added
- Lists with existing contacts retroactively push email addresses to corresponding ESP list
- Renamed MailChimp Contact Sync + Constant Contact Sync to MailChimp Connect + Constant Contact Connect
- Fixed returning contacts vs. new contacts in dashboard widget
- Contact export works again
- Fixed insecure content warning on SSL
- Non-administrators no longer can see the LeadOut menu links or pages
- Settings link missing from plugins list page
- Line break contact notifications previews
- Setup a mailto link on the contact notification email in the details header

= 2.0.0 (2014.08.11) =
= Enhancements =
- Create a custom tagged list based on form submission rules
- Ability to sync tagged contacts to a specific ESP list
- Filter lists by form selectors

- Bug fixes
- Fix contact export for selected contacts
- Text area line breaks in the contact notifications now show properly
- Contact numbers at top of list did not always match number in sidebar - fixed

= 1.3.0 (2014.07.14) =
= Enhancements =
- Multisite compatibility

= 1.2.0 (2014.06.25) =
- Bug fixes
- Contacts with default "contact status" were not showing up in the contact list
- WordPress admin backends secured with SSL can now be used with LeadOut
- Namespaced the referrer parsing library for the Sources widget

= Enhancements =
- LeadOut VIP program

= 1.1.1 (2014.06.20) =
- Bug fixes
- Emergency bug fix on activation caused by broken SVN merging

= 1.1.0 (2014.06.20) =
- Bug fixes
- LeadOut subscriber email confirmations were not sending
- Removed smart contact segmenting for leads

= Enhancements =
- Added more contact status types for contacted + customer
- Setup collection for form IDs + classes

= 1.0.0 (2014.06.12) =
- Bug fixes
- Fixed sort by visits in the contacts list

= Enhancements =
- Contacts filtering
- Stats dashboard
- Sources

= 0.10.0 (2014.06.03) =
- Bug fixes
- Fixed original referrer in contact timeline
- Fixed unnecessary queries on contact timeline
- Only run the update check if the version number is different than the saved number
- Remove "fakepath" from file path text in uploaded file input types

= Enhancements =
- Expire the subscribe cookie after a few weeks
- Ability to disable a subscribe notification
- Added jQuery validation to the subscribe pop-up
- Multi-select input support
- Block forms with credit card fields from capturing contact information
- Updated contact timeline views
- Updated new contact notification emails

= 0.9.3 (2014.05.19) =
- Bug fixes
- Fix for duplicate values being stored in the active power-ups option

= 0.9.2 (2014.05.16) =

= Enhancements =
- Overhaul of settings page to make it easier to see which settings go with each power-up
- Launched LeadOut Beta Program

= 0.9.1 (2014.05.14) =
- Bug fixes
- Fixed pop-up location dropdown not defaulting to saved options value
- Hooked subscribe widget into get_footer action instead of loop_end filter

= 0.9.0 (2014.05.12) =
- Bug fixes
- Remove leadout-css file enqueue call

= Enhancements =
- Show faces of people who viewed a post/page in the editor
- Add background color to avatars so they are easier to see
- Various UI fixes

= 0.8.5 (2014.05.08) =
- Bug fixes
- Fixed broken contact notification emails

= 0.8.4 (2014.05.07) =
- Bug fixes
- Fixed HTML encoding of apostrophes and special characters in the database for page titles

= Enhancements =
- Added ability to toggle subscribe widget on posts, pages, archives or the home page
- Sort contacts by last visit

= 0.8.3 (2014.05.06) =
- Bug fixes
- Merge duplicate contacts into one record
- Remove url parameters from source links in contact list
- Downgrade use of singletons so classes are compatible with PHP 5.2

= Enhancements =
- Swap out delete statements in favor of binary "deleted" flags to minimize data loss risk
- Sort contacts by last visit

= 0.8.2 (2014.05.02) =
- Bug fixes
- Removed namespace usage in favor or a low-tech work around to be compliant with PHP 5.2 and lower

= 0.8.1 (2014.04.30) =
- Bug fixes
- Namespaced duplicate classes

= 0.8.0 (2014.04.30) =
- Bug fixes
- Fix scrolling issue with subscribe pop-up
- Duplicate class bug fixes

= Enhancements =
- Add optional first name, last name and phone fields for subscribe pop-up
- Change out contact notification emails to be from settings email address
- Ability to disable contact notification emails
- Constant Contact list sync power-up
- Sync optional contact fields (name + phone) to email service provider power-ups

= 0.7.2 (2014.04.18) =
- Bug fixes
- Fix contact deletion bug
- Implement data recovery fix for contacts
- Bug fixes to contact merging


= 0.7.1 (2014.04.11) =
- Bug fixes
- SVN bug fix that did not add the MailChimp List sync power-up

= 0.7.0 (2014.04.10) =

= Enhancements =
- MailChimp List Sync power-up
- Added new themes (bottom right, bottom left, top and pop-up) to the WordPress Subscribe Widget power-up

= 0.6.2 (2014.04.07) =
- Bug fixes
- Fixed activation error for some installs by removing error output
- MySQL query optimizations
- Fixed bug with MySQL V5.0+ by adding default NULL values for insert statements on contacts table
- Changed title for returning lead email notifications
- Setting to change button label on 

= Enhancements =
- Added ability to change button label on subscribe widget

= 0.6.1 (2014.03.12) =
- Bug fixes
- Updated read me.txt file
- Updated screenshots

= 0.6.0 (2014.03.07) =
- Bug fixes
- Remove in-house plugin updating functionality
- Original referrer is always the server url, not the HTTP referrer
- Strip slashes from title tags
- Number of contacts does not equal leads + commenters + subscribers
- Modals aren't bound to forms after page load
- Fix bug with activating + reactivating the plugin overwriting the saved settings
- Override button styles for Subscribe Pop-up widget

= Enhancements =
- Improved readability on new lead notification emails
- Confirmation email added for new subscribers to the LeadOut Subscribe Pop-up
- Updated screenshots
- Improved onboarding flow
- Deleted unused and deprecated files

= 0.5.1 (2014.03.03) =
- Bug fixes
- Fixed Subscribe Pop-up automatically enabling itself

= 0.5.0 (2014.02.25) =
- Bug fixes
- Add (blank page title tag) to emails and contact timeline for blank page titles
- Fix link on admin nav menu bar to link to contact list
- Ignore lead notifications and subscribe popup on login page
- Saving an email no longer overwrites all the LeadOut options
- Added live chat support

= Enhancements =
- New power-ups page
- LeadOut Subscribe integrated into plugin as a power-up
- Improved contact history styling + interface
- Added visit, pageview and submission stats to the contact view
- Added Live Chat into the LeadOut WordPress admin screens
- New LeadOut icons for WordPress sidebar and admin nav menu

= 0.4.6 (2013.02.11) =
- Bug fixes
- Fix table sorting for integers
- Bug fixes to contact type headings
- Bug fix "Select All" export
- Bug fix for CSS "page views" hover triangle breaking to next line
- Backwards compatibility for < jQuery 1.7.0
- Add LeadOut link to admin bar

= Enhancements =
- New onboarding flow

= 0.4.5 (2013.01.30) =
= Enhancements =
- Integration with LeadOut Subscribe

= 0.4.4 (2013.01.24) =
- Bug fixes
- Bind submission tracking on buttons and images inside of forms instead of just submit input types

= Enhancements =
- Change out screenshots to obfiscate personal information

= 0.4.3 (2013.01.13) =
- Bug fixes
- Fixed LeadOut form submission inserts for comments
- Resolved various silent PHP warnings in administrative dashboard
- Fixed LeadOut updater class to be compatible with WP3.8
- Improved contact merging logic to be more reliable

= Enhancements =
- Improved onboarding flow
- Optimized form submission catching + improved performance

= 0.4.2 (2013.12.30) =
- Bug fixes
- Change 'contact' to 'lead' in the contacts table
- Fixed emails always sending to the admin_email
- Tie historical events to new lead when an email is submitted multiple times with different tracking codes
- Select leads, commenters and subscribers on distinct email addresses
- Fixed timeline order to show visit, then a form submission, then subsequent visits

= Enhancements =
- Added url for each page views in the contact timeline
- Added source for each visit event
- Tweak colors for contact timeline
- Default the LeadOut menu to the contacts page

= 0.4.1 (2013.12.18) =
- Bug fixes
- Removed LeadOut header from the contact timeline view
- Updated the wording on the menu view picker above contacts list
- Remove pre-mp6 styles if MP6 plugin is activated
- Default totals leads/comments = 0 when leads table is empty instead of printing blank integer
- Legacy visitors in table have 0 visits because session support did not exist. Default to 1
- Update ouput for the number of comments to be equal to total_comments, not total_leads
- Added border to pre-mp6 timeline events

= 0.4.0 (2013.12.16) =
- Bug fixes
- Block admin comment replies from creating a contact
- Fixed faulty sorting by Last visit + Created on dates in contacts list

= Enhancements =
- Timeline view of a contact history
- New CSS styles for contacts table
- Multiple email address support for new lead/comment emails
- Integration + testing for popular WordPress form builder plugins
- One click updates for manually hosted plugin

= 0.3.0 (2013.12.09) =
- Bug fixes
- HTML encoded page titles to fix broken HTML characters
- Strip slashes from page titles in emails

= Enhancements =
- Created separate LeadOut menu in WordPress admin
- CRM list of all contacts
- Added ability to export list of contacts
- LeadOut now distinguishes between a contact requests and comment submissions
- Added link to CRM list inside each contact/comment email

= 0.2.0 (2013.11.26) =
- Bug fixes
- Broke up page view history by session instead of days
- Fixed truncated form submission titles
- Updated email headers

= Enhancements =
- Plugin now updates upon activation and keeps record of version
- Added referral source to each session
- Added link to page for form submissions
- Updated email subject line
- Added social media avatars to emails

= 0.1.0 (2013.11.22) =
- Plugin released
