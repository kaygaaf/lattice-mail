# Lattice Mail — TASKS

## GROWTH OPPORTUNITIES

### SMTP Settings not linked from Lattice Mail menu (Issue #2)
**Labels:** Growth, Navigation
**Impact:** Medium
**Problem:** Lattice Mail has a "Settings" submenu item under the Lattice Mail menu, but the SMTP configuration ("SMTP Settings") is only accessible from the main dashboard's Quick Actions area — not from the Settings submenu. Users who navigate to Settings expecting email configuration find nothing there.
**Solution:** Either: (1) Move SMTP Settings into the Settings submenu page as a tab/section, or (2) Add a prominent link in the Settings page to SMTP Settings. Ensure the Settings page itself explains what it contains (e.g., "General settings for campaigns and subscribers" vs SMTP).
**Files:** `admin/partials/lattice-mail-admin-settings.php`

---

## Open Issues

### P1 — Add stat card labels to Lattice Mail dashboard (Issue #1)
**Labels:** Growth, UX Bug  
**Impact:** High  
**Problem:** Dashboard shows 3 large stat numbers (e.g., "0", "0", "0") as `<h3>` elements with no labels — no "Subscribers", "Campaigns Sent", or "Open Rate" visible. New users see three zeroes with no context.  
**Fix:** Add descriptive labels above or below each stat number ("Total Subscribers", "Active Campaigns", "Avg. Open Rate"). Add `<span class="stat-label">` or `<p class="stat-desc">` describing each metric.  
**Files:** `admin/partials/lattice-mail-admin-display.php` or similar admin view file

---

### P2-2: Email preview before send (NEW)
**Labels:** Growth, UX
**Impact:** Medium
**Problem:** When composing a campaign email, there is no way to see exactly how it will render in an inbox before sending. Users send test emails to themselves, which clutters their inbox.
**Solution:** Add a "Preview" tab in the campaign composer. Render the email HTML in an iframe showing desktop (600px) and mobile (320px) viewports side-by-side. Pull in actual subscriber merge tags (`{{first_name}}`, `{{unsubscribe_url}}`) so the preview looks identical to what recipients see. Add a "Send Test" button that emails the preview to the admin.
**Files:** `admin/partials/lattice-mail-campaign-compose.php`, CSS for preview pane
**Why it matters:** Reduces sending errors (wrong links, broken images). Improves campaign quality. Standard in Mailchimp/ConvertKit but missing in WooCommerce email plugins.
