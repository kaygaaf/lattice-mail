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

## Completed
