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

### Lattice Mail: WooCommerce Abandoned Cart Recovery
**Plugin:** Lattice Mail
**Problem:** WooCommerce stores lose 60-80% of carts at checkout. Subscribify subscription products have high cart values — losing even 10 carts per week is significant revenue leakage. Existing tools (Klaviyo, Mailchimp) are expensive and require leaving WordPress.
**Solution:** Track cart state via WooCommerce `add_to_cart` hook, storing cart snapshot in DB when user navigates away (before payment). Email capture from checkout fields. Abandoned cart sequence builder: configurable delay (30min, 1hr, 24hr, 3days) with email templates. Personalized product images and cart contents in email. Unique recovery link to restore cart. Sync with Subscribify for subscription product carts. Dashboard showing recovered revenue, conversion rate per sequence. Works alongside existing Lattice Mail subscriber system.
**Impact:** Direct revenue recovery. Abandoned cart emails typically recover 3-8% of lost carts. Premium feature with high perceived value. Essential for Subscribify users selling subscription products.
**Effort:** Medium–High

---

### GROWTH OPPORTUNITIES

**Growth: Add SMTP Setup Wizard** ✅ DONE (2026-04-10)
**Problem:** README says to configure SMTP but provides no in-plugin guidance. User must manually find their SMTP credentials and understand TLS/SSL port settings.
**Solution:** Added 3-step SMTP wizard: (1) Choose Provider — card selector with presets for SendGrid, Amazon SES, MailCow, Mailgun, Postmark, Other/Manual; (2) Enter Credentials — dynamic field labels based on provider (API Key, Access Key ID, etc.); (3) Test & Save — real SMTP connection test via PHPMailer with human-readable error messages. Provider presets auto-fill host/port/encryption. Added to class-smtp.php and class-admin.php.
**Impact:** High

**Growth: Add First-Campaign Getting Started Guide**
**Problem:** Tested admin has Lattice Mail with General and WooCommerce tabs, but no dashboard or guide explaining how to create the first campaign. User must figure out: add subscribers → create campaign → send. No guidance on WooCommerce checkout opt-in integration.
**Solution:** Add a Getting Started card on the Lattice Mail Dashboard (Lattice Mail > Home). Steps: (1) Add the subscribe form to your site using the widget or [lattice_mail_subscribe] shortcode, (2) Enable WooCommerce checkout opt-in in Settings > WooCommerce tab, (3) Create your first campaign under Campaigns > New. Link to shortcode documentation.
**Impact:** High

**Growth: WooCommerce Integration Setup Guide in Settings**
**Problem:** Lattice Mail has a WooCommerce tab in settings, but no onboarding explaining what each WooCommerce integration option does or what the opt-in checkbox looks like to customers. User has to test blindly to understand.
**Solution:** Add contextual descriptions in the WooCommerce settings tab explaining: what "Add opt-in checkbox at checkout" does, what the default label is, and how to customize it. Show a small preview mockup of what the checkout checkbox looks like. Add a "Preview Checkout" link if possible.
**Impact:** Medium
