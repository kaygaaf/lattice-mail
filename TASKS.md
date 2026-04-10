# Lattice Mail — TASKS.md

**Product:** Email management for WooCommerce
**Repo:** kaygaaf/lattice-mail
**Test Site:** https://wordpress-test.kayorama.nl

## Priority: P0 (Critical — fix immediately)
*None for now.*

## Priority: P1 (Important — this week)
*None for now.*

## Priority: P2 (Nice to have)
*None for now.*

## ⚠️ Critical Rule: No Local Files
Everything must be committed to git. If you create a new file, immediately `git add` and commit it.

## GitHub
- Issues: https://github.com/kaygaaf/lattice-mail/issues

## Development
- WordPress/WooCommerce PHP plugin
- Test on: wordpress-test.kayorama.nl

## Done ✅
- (None yet)

### GROWTH OPPORTUNITIES

**Growth: Add SMTP Setup Wizard** ✅ DONE (2026-04-10)
**Problem:** README says to configure SMTP but provides no in-plugin guidance. User must manually find their SMTP credentials and understand TLS/SSL port settings.
**Solution:** Added 3-step SMTP wizard: (1) Choose Provider — card selector with presets for SendGrid, Amazon SES, MailCow, Mailgun, Postmark, Other/Manual; (2) Enter Credentials — dynamic field labels based on provider (API Key, Access Key ID, etc.); (3) Test & Save — real SMTP connection test via PHPMailer with human-readable error messages. Provider presets auto-fill host/port/encryption. Added to class-smtp.php and class-admin.php.
**Impact:** High
