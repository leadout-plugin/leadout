# LeadOut #
**Contributors:** andygcook, nelsonjoyce
  
**Tags:**  crm, contacts, lead tracking, click tracking, visitor tracking, analytics, marketing automation, inbound marketing, subscription, marketing, lead generation, mailchimp, constant contact, newsletter, popup, popover, email list, email, contacts database, contact form, forms, form widget, popup form
  
**Requires at least:** 3.7
  
**Tested up to:** 4.2.2
  
**Stable tag:** 3.1.9
  

LeadOut is an easy-to-use marketing automation and lead tracking plugin for WordPress that helps you better understand your web site visitors.

## Description ##

### Get to know your website visitors ###

LeadOut is an easy-to-use marketing automation and lead tracking plugin for WordPress that helps you better understand your web site visitors.

### Find out who's on your site and what they're doing ###
When someone visits your site, you want to know more about them. What pages they've visited, when they return, and what social networks they’re on. LeadOut gives you the details you need to make your next move.

### More context for your conversations ###
LeadOut automatically finds publicly available information about each of your contacts. Details such as location, work history, and company info can give you more context when you reach out.

### Convert more visitors to contacts ###
Use the optional popup form to prevent people from slipping through the cracks. The popup also uses the contact data to intelligently know when to appear.

### Keep your contacts in sync with your email tool ###
LeadOut syncs your contacts to an email list of your choice without replacing any forms.

### Find out what content and traffic sources convert the best ###
Our simple analytics show you what sources of traffic and content are driving the most contacts. No more complicated Google Analytics reports.

### How does it work? ###

1. When you activate the WordPress plugin, LeadOut will track each anonymous visitor to your site with a cookie.
2. LeadOut automatically identifies and watches each existing form on your site for submissions.
3. Once someone fills out any other form on your site, LeadOut will identify that person with their email address. and add them to your contact list.
4. You'll also receive an email with a link to the new contact record with all of their visit history. (check the screenshots sections to see it in action)

### Who's using LeadOut? ###

**<a href="http://www.extremeinbound.com/leadout-wordpress-crm-inbound-plugin/" target="_blank">Alan Perlman</a>**: *“I can use LeadOut to get a sense of how engaged certain contacts are, and I can learn more about their behavior on my website to better drive the conversation and understand what they’re interested in or looking for.”*

**<a href="http://thewpvalet.com/wordpress-lead-tracking/" target="_blank">Adam W. Warner</a>**: *“…the LeadOut plugin has been very useful so far in giving us an idea of the actual visitor paths to our contact forms vs. the paths we’ve intended.”*

## Installation ##

1. Upload the 'leadout' folder to the '/wp-content/plugins/' directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add an email address under 'LeadOut' in your settings panel

## Frequently Asked Questions ##

How does LeadOut work?

When you activate the WordPress plugin, LeadOut will track each anonymous visitor to your site with a cookie.
Once someone fills out any form on your site, LeadOut will identify that person with their email address.
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

## Screenshots ##

###1. See the visit history of each contact.
###

###2. Get an email notification for every new lead.
###

###3. LeadOut stats show you where your leads are coming from.
###

###4. Segment your contact list based on page views and submissions.
###

###5. Collect more contacts with the pop-up subscribe widget.
###

###6. Create custom tagged lists, choose the form triggers to add contacts and sync your contacts to third-party email services
###
