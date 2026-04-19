# Lattice Mail — TASKS

## GROWTH OPPORTUNITIES

## ~~SMTP Settings not linked from Lattice Mail menu (Issue #2)~~ ✅ FIXED
**Labels:** Growth, Navigation
**Impact:** Medium
**Status:** Added dedicated "SMTP Settings" submenu item linking to Settings > General tab. Fixed 2026-04-19.

---

## ~~P1 — Add stat card labels to Lattice Mail dashboard (Issue #1)~~ ✅ CLOSED
**Labels:** Growth, UX Bug  
**Impact:** High  
**Status:** Labels already existed in code. Issue closed 2026-04-19.

---

### P2-2: Email preview before send (NEW)
**Labels:** Growth, UX
**Impact:** Medium
**Problem:** When composing a campaign email, there is no way to see exactly how it will render in an inbox before sending. Users send test emails to themselves, which clutters their inbox.
**Solution:** Add a "Preview" tab in the campaign composer. Render the email HTML in an iframe showing desktop (600px) and mobile (320px) viewports side-by-side. Pull in actual subscriber merge tags (`{{first_name}}`, `{{unsubscribe_url}}`) so the preview looks identical to what recipients see. Add a "Send Test" button that emails the preview to the admin.
**Files:** `admin/partials/lattice-mail-campaign-compose.php`, CSS for preview pane
**Why it matters:** Reduces sending errors (wrong links, broken images). Improves campaign quality. Standard in Mailchimp/ConvertKit but missing in WooCommerce email plugins.
