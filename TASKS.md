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

### Lattice Mail: Post-Purchase Review Request Sequence
**Plugin:** Lattice Mail
**Problem:** aipick.nl reviews AI tools and needs social proof — reviews, testimonials, user ratings. Currently there's no automated way to request reviews from purchasers or subscribers. Manual outreach is time-consuming and inconsistent. AI tool comparison posts with genuine user reviews rank better and convert more readers into affiliate clicks.
**Solution:** Add a post-purchase review trigger in Lattice Mail. Trigger fires X days after order completion (configurable: 3, 7, 14 days). Sends review request email with: product/AI tool name, order reference, direct link to review page (custom URL per product). Personalization tokens: {product_name}, {order_id}, {customer_name}. Include star rating quick-response in email (1-5 clickable links). Review responses stored in WordPress (new `lattice_mail_reviews` table). Show collected reviews in admin dashboard. Optional: auto-publish positive reviews (4-5 stars) to a testimonials page. Integration with Lattice SEO's AI Tool Comparison schema — reviews contribute to aggregate rating.
**Impact:** Direct revenue impact for aipick.nl — genuine reviews on AI tool comparison pages increase reader trust and affiliate click-through. Review sequence has among the highest ROI of any email flow. Low effort to implement on top of existing Lattice Mail auto-responder framework.
**Effort:** Low–Medium
**Priority for aipick.nl:** P1

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

### Lattice Mail: Subscriber Preference Center
**Plugin:** Lattice Mail
**Problem:** Once subscribed, customers have no way to manage what emails they receive or how often. They either stay subscribed to everything (leading to fatigue and unsubscribe) or manually email the store to ask to be removed from specific campaigns. No self-service preference management means more unsubscribes and more support load.
**Solution:** Add a "Manage Preferences" page accessible via `/email-preferences/` URL (linked in every email footer). Logged-in or email-matched subscriber sees: email frequency preference (All emails / Weekly digest / Monthly digest), topic interests (checkboxes: Product updates, Deals, AI tool reviews, Tutorials), notification opt-ins per auto-responder. Subscriber can update their profile and resubscribe to specific flows. Preferences stored in `lattice_mail_subscribers.meta`. Uses `?subscriber_hash` token in email links for non-logged-in users. GDPR-compliant: easy unsubscribe, data export option.
**Impact:** Reduces unsubscribe rates (subscribers downgrade frequency instead of full unsubscribe). Increases relevance of emails (better segmentation). Low effort to build on existing subscriber data structure.
**Effort:** Medium

### Lattice Mail: Email Subject Line AI Optimizer
**Plugin:** Lattice Mail
**Problem:** Subject lines are the #1 factor in open rates. Most WooCommerce store owners write subject lines by instinct, not data. Klaviyo and Mailchimp offer AI subject line scoring but require their platforms. No in-WordPress AI subject line tool exists.
**Solution:** In the campaign editor, add a "Subject Line Optimizer" panel: user enters their draft subject line, clicks "Analyze". Plugin checks: character count (optimal 40-60 chars), preview text integration, personalization token presence ({first_name} boosts open rate), question vs statement, emoji presence (context-dependent), power words (tested open-rate boosters). Shows a 0-100 score with breakdown. Optional: suggest 3 alternative subject lines. Uses a simple rules-based algorithm (no external API needed) — proven best practices encoded as scoring rules.
**Impact:** Measurable open rate improvement. Self-service AI without requiring external AI API or subscription. Differentiator for Lattice Mail vs basic WordPress email plugins.
**Effort:** Low–Medium

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

**Growth: Add Checkout Opt-in Checkbox Live Preview**
Problem: Even with descriptive text in settings, users still can't see what the checkout opt-in checkbox actually looks like to customers without saving and testing checkout manually. The "preview mockup" mentioned in the existing opportunity is only static text.
Solution: Add a live preview panel in the WooCommerce settings tab that renders the actual checkbox HTML as it would appear in checkout. Use JavaScript to update the preview in real-time as the label text is edited. Include a "Open Checkout Preview" button to open the store checkout in a new tab with the opt-in visible.
Impact: Medium

**Growth: Add Auto-Responder Active/Inactive Visual Status**
Problem: Auto-responders are listed in the admin but there's no visual indicator showing which ones are actively running vs paused/draft. User must check each responder's schedule to understand current status.
Solution: Add a status badge (Active / Paused / Draft) column to the auto-responders list table. Use color coding: green for active, yellow for paused, gray for draft. Add bulk toggle to activate/deactivate multiple responders at once.
Impact: Low

**Growth: Add Labels to Dashboard Stat Numbers**
Problem: The Lattice Mail Dashboard shows large "0" stat headings for Subscribers, Campaigns, and other metrics with zero visual context. The numbers appear completely unlabeled — a user seeing this for the first time has no idea what each number represents. This is a critical first-impression failure in the admin UI.
Solution: Add descriptive labels below each large number (e.g., "Total Subscribers", "Active Campaigns", "Sent This Month") or above it. Use a card layout: [label above] [big number] [subtext like "↑ 0 this week"]. Make each stat card clickable to the relevant section (click subscribers → subscriber list).
Impact: High

**Growth: Add Documentation Links to Lattice Mail Menu Items**
Problem: Each Lattice Mail admin section (Subscribers, Segments, Campaigns, Auto-Responders, Settings) has no inline help or "Learn more" links. A new user encountering a feature for the first time (e.g., Segments) has no guidance on what it does or how to use it.
Solution: Add a help icon (?) next to each section heading in the Lattice Mail admin that links to relevant documentation on latticeplugins.com. Add contextual help_tip气泡 on individual settings fields explaining what each option does.
Impact: Medium

**Growth: Add Label Above Each Stat Number on Dashboard**
Problem: The Lattice Mail Dashboard shows large "0" stat headings for Subscribers, Campaigns, and other metrics with zero visual context. The numbers appear completely unlabeled — a user seeing this for the first time has no idea what each number represents. This is a critical first-impression failure in the admin UI (e.g., e6="0", e7="0", e8="0", e9="0" — four unlabeled statistics in a row).
Solution: Add descriptive labels above each large number (e.g., "Total Subscribers", "Active Campaigns", "Sent This Month", "Open Rate") or restructure as stat cards with label above + big number + subtext like "↑ 0 this week". Make each stat card clickable to the relevant section (click "Subscribers" → subscriber list).
Impact: High

**Growth: Fix Whitespace in Mailer Radio Button Label**
Problem: The Mailer settings section shows radio button options with a leading space: " WordPress default (wp_mail)" and " SMTP". The space before "WordPress" and "SMTP" appears unintentional and looks like a rendering bug, reducing perceived quality of the admin UI.
Solution: Trim the string values used for radio button labels in the Mailer section. Ensure consistent formatting: "WordPress default (wp_mail)" and "SMTP" without leading spaces. Review all other form label strings for similar whitespace issues.
Impact: Low

**Growth: Add "How Subscribers Are Added" Explanation to Subscribers Section**
Problem: New users don't understand how Lattice Mail adds subscribers. The plugin integrates with WooCommerce checkout (opt-in checkbox), but there's no explanation in the admin UI of where subscribers come from. Users may expect to manually add subscribers or not realize the checkout integration is working.
Solution: Add a persistent info banner at the top of the Subscribers list page: "Subscribers are added automatically when customers check the opt-in box at WooCommerce checkout. You can also add subscribers manually or import a CSV list." Include links to: WooCommerce checkout settings (to configure the opt-in checkbox label) and the Import tool.
Impact: Medium

**Growth: Add Visual Campaign Status Indicator to Campaigns List**
Problem: The Campaigns section lists campaigns but shows no visual indicator of whether a campaign is Draft, Scheduled, Sending, or Sent. User must click into each campaign to determine its current state — an unnecessary friction point for daily email operations.
Solution: Add a Status column to the Campaigns list table with color-coded badges: Draft (gray), Scheduled (blue), Sending (yellow animated), Sent (green), Failed (red). Add bulk actions to pause/resume scheduled campaigns.
Impact: Medium

**Growth: Add Subscriber Count Label to Dashboard Stat Boxes**
Problem: Lattice Mail dashboard shows 4 stat boxes (Subscribers, Campaigns, etc.) but the numbers "0" have no labels above or below them. A user seeing an empty dashboard cannot determine what each box represents without clicking into each section.
Solution: Add a clear label above each stat box (e.g., "Total Subscribers", "Active Campaigns", "Sent this month"). Use larger, styled labels distinct from the number below. This immediately communicates what each metric measures.
Impact: Medium

**Growth: Add SMTP Test Button to SMTP Settings**
Problem: Users configuring SMTP have no way to verify their settings work before saving. They must save, send a test email manually, and troubleshoot if it fails.
Solution: Add a "Send Test Email" button in the SMTP settings section. Clicking it opens a modal where the user enters a test email address and clicks Send. Show success/error message inline without leaving the page.
Impact: Medium

**Growth: Add Campaign Scheduling Preview Before Publishing**
Problem: When creating a campaign, users set a scheduled send time but see no preview of when the email will arrive in the subscriber's inbox relative to their local time.
Solution: Below the schedule datetime picker, show a preview: "Email will be sent: Thursday, April 16 at 10:00 AM (your subscribers' local time zones may vary)". Add timezone indicator showing the server timezone vs selected schedule timezone.
Impact: Low

---

### GROWTH OPPORTUNITIES (2026-04-16)

**Growth: Add stat card labels to Lattice Mail dashboard**
Problem: The dashboard shows 3 large stat numbers (e.g., "0", "0", "0") as bare headings with no labels. A new user sees three zeroes with no indication of what each metric represents (Subscribers? Campaigns? Open Rate?). This is a critical clarity failure.
Solution: Add descriptive labels above or below each stat number. Example: "Total Subscribers", "Active Campaigns", "Avg. Open Rate". Add a small description paragraph below each number.
Impact: High
Issue: https://github.com/kaygaaf/lattice-mail/issues/1
