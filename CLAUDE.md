# CLAUDE.md

This file provides guidance to Claude Code when working with the SecureShare WordPress plugin.

## Git Commit Guidelines

**IMPORTANT:** When creating git commits for this project:
- Do NOT include "Generated with Claude Code" attribution
- Do NOT include "Co-Authored-By: Claude" in commit messages
- Keep commit messages professional and focused on the changes made
- This is a commercial/public project for WordPress.org submission

## Development Location

**This is the DEVELOPMENT folder** - `/var/www/html/secureshare-dev/`

- Contains the full Git repository
- Connected to GitHub: https://github.com/fatlabllc/secureshare-wordpress
- This is where you make code changes and commit
- **DO NOT** use this folder for WordPress testing

## Testing Location

For testing the plugin in WordPress:
- Download fresh ZIP from: https://github.com/fatlabllc/secureshare-wordpress/releases
- Extract to: `/var/www/html/wp-content/plugins/secureshare/`
- Test, delete, and reinstall as needed
- No impact on this dev folder

## Workflow

1. **Make changes** in `/var/www/html/secureshare-dev/`
2. **Commit and push** to GitHub
3. **Download fresh** from GitHub releases
4. **Install and test** in WordPress plugins folder
5. **If tests pass** → Tag new version and push
6. **Repeat** as needed

## Project Overview

SecureShare is a WordPress plugin for securely sharing passwords and sensitive information via time-limited, encrypted links.

### Key Features
- AES-256-CBC encryption
- Time-limited secret sharing (configurable expiration)
- IP-based rate limiting
- WordPress database storage
- REST API endpoints
- Single smart shortcode: `[secureshare]`
- Admin settings panel with statistics

### Architecture

**Main Components:**

1. **secureshare.php** - Plugin entry point
   - Plugin header and constants
   - Activation/deactivation hooks
   - Class loading and initialization

2. **includes/class-secureshare-encryption.php**
   - AES-256-CBC encryption/decryption
   - Token generation and validation
   - Uses WordPress encryption key from options

3. **includes/class-secureshare-db.php**
   - Database operations (custom tables)
   - Secret storage and retrieval
   - Rate limiting checks
   - Cleanup of expired secrets

4. **includes/class-secureshare-api.php**
   - REST API endpoints (`/secureshare/v1/create`, `/secureshare/v1/retrieve`)
   - Public endpoints (no nonce required)
   - Security via rate limiting

5. **includes/class-secureshare-shortcodes.php**
   - Single shortcode: `[secureshare]`
   - Auto-detects create vs view mode based on URL
   - Enqueues CSS/JS assets

6. **includes/class-secureshare-admin.php**
   - Settings page under Settings → SecureShare
   - Encryption key management
   - Statistics dashboard
   - Manual cleanup trigger

7. **includes/class-secureshare-cron.php**
   - Hourly cleanup of expired secrets
   - WordPress cron integration

### Templates

- **templates/shortcode-create.php** - Create secret form UI
- **templates/shortcode-view.php** - View secret UI with expiration countdown
- **templates/admin-settings.php** - Admin settings page with tabs

### Assets

- **assets/css/secureshare.css** - All plugin styles (namespaced with `.secureshare-`)
- **assets/js/secureshare.js** - Frontend form handling and AJAX
- **assets/js/admin.js** - Admin panel interactions

### Database Schema

**wp_secureshare_secrets:**
```sql
- id (bigint, auto_increment)
- token (char(32), unique)
- encrypted (longtext)
- iv (varchar(255))
- created_at (datetime)
- expires_at (datetime, indexed)
```

**wp_secureshare_rate_limits:**
```sql
- id (bigint, auto_increment)
- ip_hash (varchar(64), unique) - SHA-256 hash of IP
- request_count (int)
- window_start (datetime, indexed)
```

### Security Model

**No Nonce Verification:**
- Public REST API endpoints (anyone can create/view secrets)
- Security through rate limiting, not authentication
- This is intentional for anonymous secret sharing

**Rate Limiting:**
- IP-based (hashed with SHA-256)
- Default: 5 secrets per hour per IP
- Configurable in admin settings

**Encryption:**
- AES-256-CBC via PHP OpenSSL
- Unique IV per secret
- Key stored in wp_options
- Changing key invalidates all existing secrets

**Input Validation:**
- Max secret size (default 2000 chars)
- Token format validation (32-char hex)
- Proper sanitization on all inputs
- Output escaping on all displays

### Common Tasks

**Update Version Number:**
1. Edit `secureshare.php` line 6: `Version: X.X.X`
2. Edit `secureshare.php` line 25: `define('SECURESHARE_VERSION', 'X.X.X');`
3. Edit `readme.txt` line 7: `Stable tag: X.X.X`
4. Add changelog entry in `readme.txt`

**Add Translator Comment:**
```php
/* translators: %d: number of hours */
printf(esc_html__('Expires in %d hours', 'secureshare'), $hours);
```

**Testing Checklist:**
1. Create a secret (form works)
2. Copy the generated link
3. View the secret (displays correctly)
4. Check expiration countdown
5. Test copy-to-clipboard buttons
6. Try creating multiple secrets (rate limiting)
7. Check admin settings page
8. Verify statistics display
9. Test manual cleanup

**Before WordPress.org Submission:**
1. Run Plugin Check plugin
2. Fix any errors/warnings
3. Test on clean WordPress install
4. Verify all translator comments present
5. Check text domain is `secureshare`
6. Ensure folder name is `secureshare` (not `secureshare-wordpress-X.X.X`)
7. Create clean ZIP from GitHub release

### Important Notes

- Text domain is `secureshare` (matches WordPress.org slug)
- Plugin slug will be: `wordpress.org/plugins/secureshare/`
- Only one shortcode: `[secureshare]` (simplified in v1.0.4)
- No "burn after reading" - secrets viewable until expiration
- File-based storage removed in favor of database (WordPress version)
- Custom CSS can be added in admin settings

### File Locations

**Development:**
- `/var/www/html/secureshare-dev/` - This folder (Git repo)

**Testing:**
- `/var/www/html/wp-content/plugins/secureshare/` - WordPress plugin folder

**GitHub:**
- https://github.com/fatlabllc/secureshare-wordpress

**WordPress.org (after approval):**
- https://wordpress.org/plugins/secureshare/

### Version History

- v1.0.0 - Initial release (missing assets)
- v1.0.1 - Added JS/CSS files
- v1.0.2 - Attempted REST API nonce fix
- v1.0.3 - Removed nonce for public access (working)
- v1.0.4 - Simplified to single shortcode
- v1.0.5 - Fixed textarea width alignment
- v1.0.6 - Added translator comments for WordPress.org compliance

### Author Information

- **Author:** FatLab Web Support
- **Author URI:** https://fatlabwebsupport.com
- **Plugin URI:** https://github.com/fatlabllc/secureshare-wordpress
- **Contributors:** fatlabllc (WordPress.org username)
- **License:** GPL v2 or later
