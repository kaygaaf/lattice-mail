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
- **Core Email Marketing Plugin** (2026-04-10) — Full WooCommerce email marketing with subscriber management, campaigns, auto-responders, segmentation, SMTP wizard, and subscribe forms.

## PROPOSED FEATURES

### Lattice Mail: Email Sequence / Drip Campaigns
**Plugin:** Lattice Mail
**Problem:** WooCommerce has transactional emails but no visual campaign builder. Third-party tools (Klaviyo, Mailchimp) are expensive and require leaving WordPress.
**Solution:** Visual drag-and-drop email sequence builder. Triggers: order placed, subscription renewed, customer inactive 7/14/30 days, first purchase anniversary. Each sequence has 1-10 emails with delay configuration. Plain text and HTML editing. Queue-based sending to avoid rate limits.
**Impact:** Revenue driver — abandoned cart sequences recover 3-5% of sales. Welcome sequences increase second purchase rate. Medium effort, high revenue potential.
**Effort:** Medium–High

### Lattice Mail: Win-Back Campaign for Lapsed Subscribers
**Plugin:** Lattice Mail
**Problem:** Churned subscribers drift away silently. No automated outreach to win them back.
**Solution:** Auto-detect subscribers with no open in 45+ days. Trigger win-back sequence (3 emails over 2 weeks). Personalized discount code in email (generate unique code per user). Track win-back conversion rate in dashboard.
**Impact:** Win-back typically has 5-15% conversion — highest ROI email flow. Low effort to add on top of drip campaigns.
**Effort:** Low (add-on to drip feature)

---

### GROWTH OPPORTUNITIES

**Growth: Add SMTP Setup Wizard** ✅ DONE (2026-04-10)
**Problem:** README says to configure SMTP but provides no in-plugin guidance. User must manually find their SMTP credentials and understand TLS/SSL port settings.
**Solution:** Added 3-step SMTP wizard: (1) Choose Provider — card selector with presets for SendGrid, Amazon SES, MailCow, Mailgun, Postmark, Other/Manual; (2) Enter Credentials — dynamic field labels based on provider (API Key, Access Key ID, etc.); (3) Test & Save — real SMTP connection test via PHPMailer with human-readable error messages. Provider presets auto-fill host/port/encryption. Added to class-smtp.php and class-admin.php.
**Impact:** High
