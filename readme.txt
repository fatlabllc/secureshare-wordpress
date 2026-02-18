=== SecretDrop ===
Contributors: fatlabwebsupport
Tags: security, password, secret, encryption, sharing
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Securely share passwords and sensitive information via time-limited, encrypted links.

== Description ==

SecretDrop allows you to securely share sensitive information like passwords, API keys, and other secrets through encrypted, time-limited links. Perfect for developers, system administrators, and anyone who needs to share confidential information securely.

= Key Features =

* **AES-256-CBC Encryption** - Military-grade encryption protects your secrets
* **Time-Limited Links** - Secrets automatically expire after 24 hours (configurable)
* **No Burn After Reading** - Recipients can view secrets multiple times until expiration
* **IP-Based Rate Limiting** - Prevents abuse with configurable rate limits
* **Database Storage** - Uses custom WordPress database tables for reliable storage
* **Multiple Shortcodes** - Flexible display options with smart routing
* **Clean, Modern UI** - Responsive design works on all devices
* **No External Dependencies** - Completely self-contained plugin
* **Privacy Focused** - No external services or tracking

= How It Works =

1. Enter your secret (password, API key, etc.) in the form
2. Click "Create Secure Link" to generate an encrypted link
3. Share the link with the intended recipient
4. Recipient opens the link to view the secret
5. Secret automatically expires after configured time period (default: 24 hours)

= Security Features =

* **Strong Encryption**: Uses PHP's OpenSSL extension with AES-256-CBC
* **Unique Keys**: Each secret gets a unique 32-character token
* **Random IVs**: Initialization vectors prevent pattern detection
* **Input Sanitization**: All inputs are properly sanitized and validated
* **Output Escaping**: All outputs are properly escaped for XSS prevention
* **Nonce Protection**: CSRF protection on all forms and REST API requests
* **Rate Limiting**: Configurable IP-based rate limiting prevents abuse

= Shortcode =

Use the `[secretdrop]` shortcode on any page or post. It automatically detects whether to show the create form or view interface based on the URL.

* When visited normally (e.g., `/share/`) - Shows the create secret form
* When visited with a token (e.g., `/share/?token=abc123`) - Shows the secret viewer

Simply add `[secretdrop]` to your page content and it handles everything!

= Admin Features =

* **Encryption Key Management**: Auto-generate secure encryption keys
* **Configurable Expiration**: Set custom expiration times
* **Rate Limit Controls**: Configure abuse prevention settings
* **Statistics Dashboard**: View plugin usage statistics
* **Manual Cleanup**: Trigger cleanup of expired secrets
* **Custom CSS**: Add custom styling to match your theme
* **Debug Mode**: Enable logging for troubleshooting

= Use Cases =

* Sharing database credentials with developers
* Sending API keys to team members
* Sharing temporary passwords with clients
* Distributing WiFi passwords securely
* Exchanging sensitive configuration data
* Any scenario requiring secure, temporary information sharing

= Requirements =

* WordPress 6.0 or higher
* PHP 7.4 or higher
* OpenSSL PHP extension (standard on most servers)
* MySQL/MariaDB database

== Installation ==

= Automatic Installation =

1. Log in to your WordPress admin panel
2. Navigate to Plugins > Add New
3. Search for "SecretDrop"
4. Click "Install Now" and then "Activate"
5. Go to Settings > SecretDrop to configure

= Manual Installation =

1. Download the plugin zip file
2. Extract the zip file
3. Upload the `secretdrop` folder to `/wp-content/plugins/`
4. Activate the plugin through the 'Plugins' menu in WordPress
5. Go to Settings > SecretDrop to configure

= After Installation =

1. Create a new page (e.g., "Share Secret")
2. Add the `[secretdrop]` shortcode to the page
3. Publish the page
4. Users can now create and view secrets from that page

= Configuration =

1. Navigate to Settings > SecretDrop
2. Review the automatically generated encryption key
3. Adjust expiration time if needed (default: 24 hours)
4. Configure rate limiting settings
5. Optionally add custom CSS

== Frequently Asked Questions ==

= Is this plugin secure? =

Yes! SecretDrop uses AES-256-CBC encryption, the same standard used by banks and governments. Each secret is encrypted with a unique initialization vector, and the encryption key is stored securely in your WordPress database.

= Can secrets be viewed more than once? =

Yes, secrets can be viewed multiple times until they expire. This is not a "burn after reading" system - the secret remains available until the expiration time.

= What happens when a secret expires? =

Expired secrets are automatically deleted from the database by a scheduled cron job that runs hourly. You can also manually trigger cleanup from the admin panel.

= Can I change the expiration time? =

Yes! Go to Settings > SecretDrop > General and adjust the "Expiration Time" setting. The default is 86400 seconds (24 hours).

= Does this work with any WordPress theme? =

Yes! The plugin uses namespaced CSS classes to avoid conflicts with your theme. If you experience styling issues, you can add custom CSS in the Advanced settings tab.

= Can I limit how many secrets users can create? =

Yes! Rate limiting is enabled by default. Go to Settings > SecretDrop > Rate Limiting to configure the maximum number of secrets per IP address per time window.

= What if I change the encryption key? =

WARNING: Changing the encryption key will make all existing secrets unrecoverable. Only regenerate the key if you need to invalidate all existing secrets.

= Is multisite supported? =

Version 1.1 is designed for single-site WordPress installations. Multisite support may be added in a future version.

= Does this plugin phone home or use external services? =

No! SecretDrop is completely self-contained. It doesn't make any external API calls or send data to third-party services.

= Can I customize the appearance? =

Yes! You can add custom CSS in Settings > SecretDrop > Advanced. All plugin elements use the `.secretdrop-` CSS prefix. See the "CSS Customization Guide" section below for detailed examples.

== CSS Customization Guide ==

All SecretDrop elements are namespaced with `.secretdrop-` to avoid conflicts with your theme. You can customize the appearance by adding CSS to Settings > SecretDrop > Advanced > Custom CSS.

= Color Scheme Customization =

**Primary Button Color (Create Secure Link button):**
```css
.secretdrop-button-primary {
    background-color: #your-brand-color;
    border-color: #your-brand-color;
}
.secretdrop-button-primary:hover {
    background-color: #your-darker-color;
    border-color: #your-darker-color;
}
```

**Secondary Button Color (Copy buttons):**
```css
.secretdrop-button-secondary {
    background-color: #6c757d;
    border-color: #6c757d;
}
```

**Info Boxes (Security features display):**
```css
.secretdrop-info-box {
    background: #e8f4f8;
    border: 1px solid #bee5eb;
}
```

**Warning Boxes:**
```css
.secretdrop-warning-box {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
}
```

= Typography Customization =

**Change Font Family:**
```css
.secretdrop-container {
    font-family: 'Your Font', sans-serif;
}
```

**Secret Display Font (monospace for passwords/keys):**
```css
.secretdrop-secret-content {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', monospace;
    font-size: 1rem;
}
```

**Heading Styles:**
```css
.secretdrop-header h2 {
    color: #2c3e50;
    font-size: 2.5rem;
    font-weight: 700;
}
```

= Layout Customization =

**Container Width:**
```css
.secretdrop-container {
    max-width: 1000px; /* Default: 800px */
}
```

**Form Padding:**
```css
.secretdrop-form {
    padding: 3rem; /* Default: 2rem */
}
```

**Secret Box Styling:**
```css
.secretdrop-secret-box {
    padding: 2.5rem;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}
```

= Button Customization =

**Rounded Buttons:**
```css
.secretdrop-button {
    border-radius: 50px; /* Pill-shaped buttons */
}
```

**Larger Buttons:**
```css
.secretdrop-button {
    padding: 1rem 2rem;
    font-size: 1.125rem;
}
```

**Remove Button Hover Effects:**
```css
.secretdrop-button:hover {
    transform: none;
    box-shadow: none;
}
```

= Form Element Customization =

**Textarea Styling:**
```css
.secretdrop-textarea {
    background-color: #f8f9fa;
    border: 2px solid #dee2e6;
    font-size: 1.125rem;
    min-height: 200px;
}
.secretdrop-textarea:focus {
    border-color: #your-brand-color;
    box-shadow: 0 0 0 0.2rem rgba(your-rgb, 0.25);
}
```

**Character Counter Color:**
```css
.secretdrop-char-counter {
    color: #your-color;
    font-size: 1rem;
}
```

= Success/Error Message Customization =

**Success Messages:**
```css
.secretdrop-success {
    background-color: #d4edda;
    border-color: #c3e6cb;
    color: #155724;
}
```

**Error Messages:**
```css
.secretdrop-error {
    background-color: #f8d7da;
    border-color: #f5c6cb;
    color: #721c24;
}
```

**Success Icon (checkmark circle):**
```css
.secretdrop-success-icon {
    background: #28a745;
    width: 80px;
    height: 80px;
    font-size: 2.5rem;
}
```

= Dark Mode Example =

**Complete Dark Mode Override:**
```css
.secretdrop-container {
    color: #e4e4e4;
}
.secretdrop-form,
.secretdrop-secret-box {
    background: #2d2d2d;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
}
.secretdrop-header h2,
.secretdrop-view-header h2 {
    color: #ffffff;
}
.secretdrop-textarea,
.secretdrop-secret-content {
    background: #1a1a1a;
    border-color: #444;
    color: #e4e4e4;
}
.secretdrop-info-box {
    background: #1a3a4a;
    border-color: #2a5a6a;
}
```

= Accessibility Improvements =

**High Contrast Mode:**
```css
.secretdrop-button-primary {
    background-color: #000;
    border: 3px solid #000;
}
.secretdrop-button-primary:hover {
    background-color: #333;
}
```

**Larger Text for Better Readability:**
```css
.secretdrop-container {
    font-size: 1.125rem;
}
.secretdrop-button {
    font-size: 1.25rem;
    padding: 1rem 2rem;
}
```

= Mobile Customization =

**Adjust Mobile Breakpoints:**
```css
@media (max-width: 768px) {
    .secretdrop-container {
        padding: 0 10px;
    }
    .secretdrop-form {
        padding: 1.5rem;
    }
}
```

= Complete Brand Integration Example =

**Match Your Brand Colors:**
```css
/* Brand Primary Color: #FF6B35 (example) */
.secretdrop-button-primary {
    background-color: #FF6B35;
    border-color: #FF6B35;
}
.secretdrop-button-primary:hover {
    background-color: #E85A2B;
    border-color: #E85A2B;
}
.secretdrop-textarea:focus {
    border-color: #FF6B35;
    box-shadow: 0 0 0 0.2rem rgba(255, 107, 53, 0.25);
}
.secretdrop-info-box {
    background: #FFF5F2;
    border-color: #FFCAB8;
}
.secretdrop-info-box li::before {
    color: #FF6B35;
}
```

= Tips for Custom CSS =

1. **Use Browser DevTools**: Right-click on any element and select "Inspect" to see its CSS classes
2. **Specificity**: All classes use `.secretdrop-` prefix. Use this in your selectors for proper specificity
3. **!important**: Avoid using `!important` unless necessary. The CSS is designed to be easily overridable
4. **Test Responsive**: Always test your customizations on mobile devices
5. **Copy Before Editing**: Copy the original CSS rules before modifying to easily revert changes

= Finding CSS Classes =

All major elements have descriptive classes:
- `.secretdrop-container` - Main wrapper
- `.secretdrop-form` - Create secret form
- `.secretdrop-textarea` - Secret input textarea
- `.secretdrop-button` - All buttons
- `.secretdrop-button-primary` - Primary action buttons
- `.secretdrop-button-secondary` - Secondary action buttons
- `.secretdrop-secret-box` - Secret display container
- `.secretdrop-secret-content` - The actual secret text
- `.secretdrop-info-box` - Information boxes
- `.secretdrop-warning-box` - Warning boxes
- `.secretdrop-result` - Success result display
- `.secretdrop-url-input` - Generated URL input field

For a complete list of classes, view the plugin's CSS file at:
`wp-content/plugins/secretdrop/assets/css/secretdrop.css`

== Screenshots ==

1. Create secret form with character counter and security features
2. Generated secure link with copy button
3. View secret interface with expiration countdown
4. Admin settings - General tab
5. Admin settings - Statistics dashboard

== Changelog ==

= 1.1.0 - 2025-11-23 =
* Rename: Plugin renamed from SecureShare to SecretDrop (new slug: secretdrop)
* Security: Added WP REST nonce (X-WP-Nonce header) for CSRF protection on secret creation
* Security: Documented intentional public access on REST API permission callbacks
* Update: All internal identifiers, text domain, CSS classes, and shortcodes renamed to secretdrop
* Update: Shortcode changed from [secureshare] to [secretdrop]

= 1.0.7 - 2025-11-12 =
* Fix: WordPress.org Plugin Check compliance - all critical errors resolved
* Fix: Global variable prefixes added to all template variables
* Fix: Changed date() to gmdate() for timezone-safe operations
* Fix: Changed wp_redirect() to wp_safe_redirect() for security
* Fix: Improved output escaping and input sanitization
* Fix: Fixed unordered placeholders in translations (i18n compliance)
* Fix: Added translator comments for all strings with placeholders
* Fix: Removed deprecated load_plugin_textdomain() function
* Fix: Updated "Tested up to" WordPress version to 6.8
* Improvement: Created languages directory for translation files

= 1.0.6 - 2025-11-12 =
* Fix: Add translator comments for all translatable strings with placeholders
* Compliance: WordPress.org Plugin Check requirements for internationalization
* Add /* translators: */ comments to clarify placeholder meanings for translators

= 1.0.5 - 2025-11-12 =
* Fix: Textarea width alignment - now matches button and info box width
* Add box-sizing: border-box to textarea for consistent sizing

= 1.0.4 - 2025-11-12 =
* Simplification: Removed separate [secureshare_create] and [secureshare_view] shortcodes
* Only [secureshare] shortcode remains - automatically detects create vs view mode
* Updated documentation to reflect simplified usage
* Cleaner, easier-to-understand implementation for v1.0

= 1.0.3 - 2025-11-12 =
* Fix: Remove nonce verification requirement for public REST API endpoints
* Fix: Update plugin version numbers in all files
* Security is maintained through IP-based rate limiting

= 1.0.2 - 2025-11-12 =
* Fix: Correct WordPress REST API nonce handling (use X-WP-Nonce header)
* Attempted fix for "Security check failed" error

= 1.0.1 - 2025-11-12 =
* Fix: Add missing JavaScript and CSS asset files
* Add frontend form handling and AJAX functionality
* Add copy-to-clipboard functionality
* Add character counter
* Add admin panel JavaScript
* Add complete responsive CSS styles

= 1.0.0 - 2025-11-11 =
* Initial release
* AES-256-CBC encryption
* Time-limited secret sharing (24-hour default)
* IP-based rate limiting
* Custom database tables
* Three shortcode options
* Admin settings panel
* Statistics dashboard
* Automatic cleanup via WP-Cron
* Responsive design
* WordPress Coding Standards compliant

== Upgrade Notice ==

= 1.1.0 =
Plugin renamed to SecretDrop. All shortcodes changed from [secureshare] to [secretdrop]. Update your pages accordingly.

= 1.0.0 =
Initial release of the plugin.

== Privacy Policy ==

SecretDrop is designed with privacy in mind:

* **No External Services**: The plugin doesn't communicate with any external services
* **IP Hashing**: IP addresses for rate limiting are hashed using SHA-256
* **Automatic Deletion**: Secrets are automatically deleted after expiration
* **No Logging**: Secrets are not logged (unless debug mode is enabled)
* **Local Storage**: All data is stored in your WordPress database

If you enable debug mode, cleanup activities will be logged to your PHP error log. Disable debug mode in production to avoid logging.

== Support ==

For support, please:

1. Check the FAQ section above
2. Visit the plugin's GitHub repository: https://github.com/fatlabllc/secretdrop-wordpress
3. Contact FatLab Web Support: https://fatlabwebsupport.com

== Credits ==

Developed by FatLab Web Support (https://fatlabwebsupport.com)

Uses PHP's OpenSSL extension for encryption.

== License ==

This plugin is licensed under the GPLv2 or later.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
