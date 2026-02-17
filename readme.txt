=== Revora ===
Contributors: moksedul
Tags: reviews, testimonials, rating, elementor, ajax
Requires at least: 5.8
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, category-based review system with AJAX submission, spam detection, admin moderation, and beautiful Elementor widgets.

== Description ==

**Revora** is a powerful yet lightweight WordPress review plugin that lets you collect, manage, and display customer reviews with ease. Built with performance and user experience in mind, Revora uses a custom database table for optimal speed and includes advanced features like spam detection, AJAX submissions, and full Elementor integration.

= Key Features =

* **Category-Based Reviews** - Organize reviews by custom categories
* **AJAX Form Submission** - Smooth, no-reload review submissions
* **Smart Spam Detection** - Built-in anti-spam system
* **Admin Moderation Panel** - Easy review management dashboard
* **Elementor Integration** - 3 custom widgets with full styling controls
* **Card Design Variants** - 5 professional card styles to choose from
* **Responsive Design** - Mobile-friendly and touch-enabled
* **SEO Optimized** - Schema markup for rich snippets
* **Email Notifications** - Get notified of new reviews
* **Load More Pagination** - AJAX-powered infinite scroll

= Elementor Widgets =

1. **Review Form Widget** - Customizable review submission form
2. **Reviews Display Widget** - Grid layout with responsive columns
3. **Reviews Slider Widget** - Carousel with autoplay and navigation

= Card Design Styles =

* **Classic** - Standard vertical layout
* **Modern** - Minimal, centered design
* **Boxed** - Elevated with colored header
* **Horizontal** - Side-by-side layout
* **Testimonial** - Quote-style presentation

= Privacy & Data =

Revora collects and stores the following user information when a review is submitted:

* **Name** - To display the reviewer's identity
* **Email** - For verification and notifications (not publicly displayed)
* **Review Content** - Title and detailed review text
* **Rating** - Star rating (1-5)
* **IP Address** - For spam detection and security
* **Submission Date** - Timestamp of review submission

All data is stored in a custom WordPress database table and can be deleted by the site administrator at any time through the admin panel.

= Security Features =

* Nonce verification on all forms
* Input sanitization and validation
* Output escaping for XSS prevention
* Prepared SQL statements
* CSRF protection
* Rate limiting for spam prevention

== Installation ==

1. Upload the `revora` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Revora > Settings** to configure the plugin
4. Create review categories in **Revora > Categories**
5. Use shortcodes or Elementor widgets to display reviews

= Shortcodes =

* `[revora_form category="your-category"]` - Display review submission form
* `[revora_reviews category="your-category" limit="6" columns="3"]` - Display reviews grid

= Elementor Usage =

1. Edit any page with Elementor
2. Search for "Revora" in the widget panel
3. Drag and drop widgets onto your page
4. Customize with full styling controls

== Frequently Asked Questions ==

= Does this plugin require Elementor? =

No, Elementor is optional. You can use shortcodes to display reviews without Elementor. However, Elementor widgets provide more customization options.

= How do I moderate reviews? =

Go to **Revora > All Reviews** in your WordPress admin panel. You can approve, reject, or delete reviews from there.

= Can I customize the review form? =

Yes! You can customize colors, typography, spacing, and more through the Elementor widget controls or via custom CSS in the settings.

= Is the plugin GDPR compliant? =

Yes, Revora stores minimal user data and provides clear information about what data is collected. Site administrators can delete user data at any time.

= Does it work with caching plugins? =

Yes, Revora is compatible with popular caching plugins. AJAX functionality works seamlessly with cached pages.

= Can I import/export reviews? =

This feature is planned for a future update. Currently, reviews are stored in the WordPress database and can be accessed via phpMyAdmin if needed.

== Screenshots ==

1. Review submission form with star rating
2. Admin moderation panel
3. Reviews grid display
4. Elementor widget controls
5. Card design variants
6. Reviews slider carousel
7. Settings page
8. Category management

== Changelog ==

= 1.0.1 - 2026-02-18 =
* Added: Elementor integration with 3 custom widgets
* Added: 5 card design variants (Classic, Modern, Boxed, Horizontal, Testimonial)
* Added: Reviews Slider widget with Swiper.js
* Added: Load more pagination (AJAX)
* Added: Form input placeholders
* Improved: Submit button styling
* Improved: Frontend CSS organization
* Fixed: Minor CSS issues

= 1.0.0 - 2026-02-17 =
* Initial release
* Category-based review system
* AJAX form submission
* Smart spam detection
* Admin moderation panel
* Email notifications
* SEO schema markup
* Responsive design
* Shortcode support

== Upgrade Notice ==

= 1.0.1 =
Major update with Elementor integration and card design variants. Backup your database before updating.

= 1.0.0 =
Initial release of Revora.

== Privacy Policy ==

Revora collects and stores the following information when users submit reviews:

**Data Collected:**
* Name (required)
* Email address (required, not publicly displayed)
* Review title and content (required)
* Star rating (required)
* IP address (for spam detection)
* Submission timestamp

**How We Use This Data:**
* Display reviews on your website
* Send email notifications to administrators
* Detect and prevent spam submissions
* Moderate and manage reviews

**Data Retention:**
Review data is stored indefinitely until manually deleted by a site administrator through the Revora admin panel.

**User Rights:**
Users can request deletion of their review data by contacting the site administrator. Administrators can delete reviews from the Revora admin panel.

**Third-Party Services:**
Revora does not send data to any third-party services or external APIs.

== Support ==

For support, feature requests, or bug reports, please visit:
https://github.com/codermoksedul/revora

== Credits ==

* Developed by Moksedul Islam
* Swiper.js library (MIT License) - https://swiperjs.com/
* WordPress Dashicons (GPL License)
