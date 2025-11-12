# SecureShare Development Workflow

## 📂 Directory Structure

### Development Folder (This Folder)
**Location:** `/var/www/html/secureshare-dev/`

This is your **source of truth** for development:
- ✅ Connected to Git
- ✅ Push/pull from GitHub
- ✅ Contains CLAUDE.md (local only, not in Git)
- ✅ Make all code changes here
- ✅ Safe from accidental deletion

**Never use this folder for WordPress testing!**

### WordPress Testing Folder
**Location:** `/var/www/html/wp-content/plugins/secureshare/`

This is where you **test the plugin**:
- ✅ Download fresh from GitHub releases
- ✅ Safe to delete and reinstall repeatedly
- ✅ No Git repository here
- ✅ No dev files (CLAUDE.md, etc.)

---

## 🔄 Development Workflow

### 1. Make Changes (In Dev Folder)

```bash
cd /var/www/html/secureshare-dev/

# Make your code changes
# Edit files as needed

# Check what changed
git status
git diff

# Stage and commit
git add .
git commit -m "Your commit message"

# Push to GitHub
git push origin main
```

### 2. Test Changes (Fresh Install)

```bash
# Go to WordPress plugins folder
cd /var/www/html/wp-content/plugins/

# Delete old test version
rm -rf secureshare/

# Download latest from GitHub
wget https://github.com/fatlabllc/secureshare-wordpress/archive/refs/heads/main.zip
unzip main.zip
mv secureshare-wordpress-main secureshare

# Or download a specific version:
# wget https://github.com/fatlabllc/secureshare-wordpress/archive/refs/tags/v1.0.6.zip
```

### 3. Create New Version

```bash
cd /var/www/html/secureshare-dev/

# Update version numbers in:
# - secureshare.php (line 6 and line 25)
# - readme.txt (line 7 and changelog)

# Commit version bump
git add secureshare.php readme.txt
git commit -m "Bump version to X.X.X"
git push origin main

# Create and push tag
git tag -a vX.X.X -m "Version X.X.X release notes"
git push origin vX.X.X
```

### 4. Test Before Submission

```bash
# Download the tagged release
cd /var/www/html/wp-content/plugins/
rm -rf secureshare/
wget https://github.com/fatlabllc/secureshare-wordpress/archive/refs/tags/vX.X.X.zip
unzip vX.X.X.zip
mv secureshare-wordpress-X.X.X secureshare

# Test in WordPress
# - Activate plugin
# - Test creating secrets
# - Test viewing secrets
# - Check admin settings
# - Run Plugin Check
```

### 5. Submit to WordPress.org

Once tests pass:

1. Download: https://github.com/fatlabllc/secureshare-wordpress/archive/refs/tags/vX.X.X.zip
2. Extract and verify folder name is `secureshare` (not `secureshare-wordpress-X.X.X`)
3. Run Plugin Check one final time
4. Submit to: https://wordpress.org/plugins/developers/add/

---

## ⚠️ Important Rules

### ✅ DO:
- Make all code changes in `/var/www/html/secureshare-dev/`
- Commit and push from the dev folder
- Download fresh versions for testing
- Keep CLAUDE.md in dev folder only
- Use Plugin Check before submitting

### ❌ DON'T:
- Don't edit files in `/var/www/html/wp-content/plugins/secureshare/`
- Don't commit CLAUDE.md to Git (it's in .gitignore)
- Don't use dev folder for WordPress testing
- Don't submit ZIP with `secureshare-wordpress` folder name

---

## 🗂️ What's in Each Location

### `/var/www/html/secureshare-dev/` (Development)
```
secureshare-dev/
├── .git/              ← Git repository
├── .gitignore         ← Ignores CLAUDE.md
├── CLAUDE.md          ← Dev docs (NOT in Git)
├── WORKFLOW.md        ← This file
├── secureshare.php    ← Main plugin file
├── readme.txt         ← WordPress.org readme
├── uninstall.php
├── assets/
│   ├── css/
│   └── js/
├── includes/
│   └── class-*.php
└── templates/
    └── *.php
```

### `/var/www/html/wp-content/plugins/secureshare/` (Testing)
```
secureshare/           ← Downloaded from GitHub
├── secureshare.php    ← Same files as dev
├── readme.txt         ← But NO .git, NO CLAUDE.md
├── uninstall.php
├── assets/
├── includes/
└── templates/
```

---

## 🔍 Quick Commands

### Check Current Version
```bash
cd /var/www/html/secureshare-dev/
grep "Version:" secureshare.php
grep "Stable tag:" readme.txt
git tag | tail -1
```

### Compare Dev vs Test
```bash
# See what's in dev
ls -la /var/www/html/secureshare-dev/

# See what's in test
ls -la /var/www/html/wp-content/plugins/secureshare/
```

### Clean Test Folder
```bash
rm -rf /var/www/html/wp-content/plugins/secureshare/
```

---

## 📚 Resources

- **GitHub Repo:** https://github.com/fatlabllc/secureshare-wordpress
- **WordPress.org:** https://wordpress.org/plugins/secureshare/ (after approval)
- **Plugin Check:** Install from WordPress plugins directory
- **Documentation:** See CLAUDE.md for detailed architecture notes

---

## 🆘 Troubleshooting

**Problem:** I accidentally edited files in the test folder
- **Solution:** Just delete `/var/www/html/wp-content/plugins/secureshare/` and re-download from GitHub

**Problem:** I committed CLAUDE.md to Git
- **Solution:** Run `git rm --cached CLAUDE.md` then commit

**Problem:** Plugin Check says wrong text domain
- **Solution:** Make sure folder name is `secureshare` not `secureshare-wordpress-X.X.X`

**Problem:** Changes not showing up in WordPress
- **Solution:** Delete test folder and reinstall from GitHub to get latest changes

---

**Last Updated:** November 12, 2025
**Current Version:** 1.0.6
**Status:** Ready for WordPress.org submission
