# Lattice Mail

Email marketing and subscriber management plugin for WordPress & WooCommerce.

## Features

- **Subscriber Management** — Add, confirm, and manage email subscribers
- **Campaign Creation** — Create and send HTML email campaigns
- **WooCommerce Integration** — Opt-in checkbox at checkout
- **SMTP Support** — Connect to any SMTP provider
- **REST API** — Manage subscribers and campaigns programmatically
- **WP-CLI Commands** — Full command-line interface
- **Shortcodes** — `[lattice_mail_subscribe]` for subscribe forms
- **Widgets** — Subscribe widget for sidebars

## Installation

1. Upload `lattice-mail` folder to `/wp-content/plugins/`
2. Activate through WordPress plugins menu
3. Go to **Lattice Mail > Settings** to configure SMTP
4. Use `[lattice_mail_subscribe]` shortcode or the Subscribe widget

## Shortcode

```
[lattice_mail_subscribe title="Subscribe" show_name="true" button_text="Subscribe"]
```

## WP-CLI Commands

```bash
# List subscribers
wp lattice-mail list_subscribers
wp lattice-mail list_subscribers --status=active

# Add subscriber
wp lattice-mail add_subscriber user@example.com --name="John Doe"

# List campaigns
wp lattice-mail list_campaigns

# Create campaign
wp lattice-mail create_campaign --subject="My Campaign" --content="<p>Hello!</p>"

# Send campaign
wp lattice-mail send_campaign 1
```

## REST API

Base URL: `/wp-json/lattice-mail/v1/`

### Subscribers

- `GET /subscribers` — List all subscribers
- `POST /subscribers` — Add subscriber (`{email, name}`)
- `GET /subscribers/{id}` — Get subscriber
- `DELETE /subscribers/{id}` — Delete subscriber

### Campaigns

- `GET /campaigns` — List all campaigns
- `POST /campaigns` — Create campaign (`{subject, content}`)
- `GET /campaigns/{id}` — Get campaign
- `POST /campaigns/{id}/send` — Send campaign
- `DELETE /campaigns/{id}` — Delete campaign

## WooCommerce

Enable in **Lattice Mail > Settings > WooCommerce** tab. Adds an opt-in checkbox to checkout.

## SMTP Configuration

Supports any SMTP provider (MailCow, SendGrid, SES, etc.):

- Host, Port, Username, Password
- TLS or SSL encryption
- Test connection from settings page

## Requirements

- WordPress 6.0+
- PHP 8.0+
- WooCommerce (optional, for WooCommerce integration)

## License

GPL v2
