# Custom PMPro Membership Card Template

This is a custom template for the Paid Memberships Pro "Membership Card" Add On that overrides the default card layout with a more modern, two-column design.

## Features

- Two-column layout with profile information on the left and QR code on the right
- Profile picture (avatar) display
- Displays member's name, email, level, ID, and membership dates
- Site logo in the top-right corner
- QR code for membership verification
- Fully responsive design that adapts to mobile devices
- Print-friendly styling

## Installation Instructions

1. Make sure you have the [PMPro Membership Card Add On](https://www.paidmembershipspro.com/add-ons/pmpro-membership-card/) installed and activated.

2. Place the `membership-card.php` file in your child theme directory.
   - If you're using a child theme: `/wp-content/themes/your-child-theme/`
   - Alternatively, you can place it in: `/wp-content/themes/your-theme/`

3. The template will automatically override the default membership card template.

## Usage

Use the shortcode in any WordPress page or post:

```
[pmpro_membership_card]
```

### Shortcode Options

The template supports all standard PMPro Membership Card shortcode attributes:

- `print_size`: Specify what sizes to include in the "print" view. Default: "all". Options: "small", "medium", "large", "all".
- `qr_code`: Display a QR code on the card. Default: "false". Options: "true", "false".
- `qr_data`: Specify what data the QR code should contain. Default: "ID". Options: "email", "ID", "level", "other".

Example with all options:

```
[pmpro_membership_card qr_code="true" qr_data="email" print_size="large"]
```

## Customization

You can customize the card appearance by modifying the CSS in the `membership-card.php` file. The template includes inline styles for easy customization.

Key styling variables:
- Main accent color: `#BB9A2A` (gold)
- Background color: `#ffffff`
- Primary text color: `#071938`
- Secondary text color: `#667085`

## Support

For support with the Paid Memberships Pro plugin or add-ons, please visit:
[PMPro Support](https://www.paidmembershipspro.com/support/)
