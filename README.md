Provides recurring billing for Drupal Commerce, powered by Advanced queue.

The successor to Commerce Recurring and Commerce License Billing for D7.

Features:
- Configurable billing intervals (charge every N days/weeks/months/years).
- Fixed and rolling interval types (charge on the 1st of the month VS 1 month from the subscription date)
- Prepaid and postpaid payment types (charge at the beginning or at the end of the billing period).
- Free trial of any interval (14 days followed by a regular monthly subscription, etc)
- Prorating (adjusting the charged price based on the duration of its usage)
- Usage tracking (track bandwidth and charge per GB, etc).

## Use cases

1) Recurring membership (via commerce_license)

Prepaid billing for a license (usually of type "role").

2) Recurring SaaS subscription

Postpaid billing, with optional usage, for a license.

3) Donations

Prepaid billing for a product variation (no license), or an order item without a purchasable entity.
Customers can usually select between multiple billing schedules (monthly/yearly, etc).

Future use cases: Physical products (Dollar Shave Club, etc)
