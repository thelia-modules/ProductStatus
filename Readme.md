# Product Status

Assigns one status (code, color, title and description translated) to each product. A product without
status reads as the protected `normal` status. Four statuses are protected and cannot be deleted:
`normal`, `discontinued`, `sale`, `oddment`.

Thelia 3.2 or later. The 2.x line of this module is for Thelia 2.

## Installation

```
composer require thelia/product-status-module:^3.0
php bin/console module:refresh
php bin/console module:activate ProductStatus
```

The installation script (`Config/TheliaMain.sql`) runs once, on the first activation, and drops nothing:
on a database carried over from the 2.x line the existing statuses and links are kept.

`Config/update/3.0.0.sql` makes the status unique per product: it deletes the duplicate links, keeping
the oldest one of each product (the one the 2.x line displayed), then adds a unique index on
`product_product_status.product_id`. It runs on the upgrade from 2.x and at every activation, and does
nothing once the index exists. The upgrade from 2.x and every activation also clean the stored
descriptions with the rules of `save()`.

## Usage

### Service

`ProductStatus\Service\ProductStatusService` (autowired) is the single entry point:

| Method | |
|---|---|
| `statusOf(int $productId, string $locale): ProductStatusView` | status of a product, `normal` when it has none (read only) |
| `statusesOf(array $productIds, string $locale): array` | `array<int, ProductStatusView>` keyed by product id, two queries whatever the number of products |
| `all(string $locale): array` | every status, ordered by id |
| `assign(int $productId, int $statusId): void` | replaces the status of the product; dispatches `ProductStatusEvents::PRODUCT_STATUS_CHANGED` only when it changes |
| `save(?int $id, string $code, string $color, string $title, ?string $description, string $locale): int` | creates or updates a status; the code is lower-cased, unique and a slug (`a-z`, `0-9`, `-`, `_`) when it is new or changed (a 2.x code with spaces can be kept as is), the code of a protected status cannot change, the color is `#rgb` or `#rrggbb` (stored `#rrggbb`), the description keeps only `<b><strong><i><em><a><br>` without any attribute but the `href` of an http(s), mailto or relative link |
| `translations(string $locale): array` | titles and descriptions stored in `$locale`, without fallback |
| `delete(int $id): void` | refuses a protected status; the products of the deleted status read `normal` again and each dispatches `PRODUCT_STATUS_CHANGED` |

Business errors are `ProductStatus\Exception\ProductStatusException` (a `\DomainException`); an unknown
status id is an `UnknownProductStatusException` (an `\InvalidArgumentException`). Both implement
`ProductStatusErrorInterface`, whose `translationKey()` is a message of the `productstatus` domain.
Titles and descriptions missing in the requested locale fall back to the default language, unless the
shop strictly uses the requested language (`default_lang_without_translation` = 0); a status without any
title then shows its code.

### Event

`productstatus.product_status.changed` (`ProductStatus\Event\ProductStatusChangedEvent`: `productId`,
`previousStatusId`, null when the product had no status, `statusId`).

### API

`/api/front/products` and `/api/front/products/{id}` carry the status of the product:

```json
"ProductStatusAddon": {
  "productStatus": {"code": "sale", "title": "Sale", "description": "clearance sale", "color": "#986dff"}
}
```

The title and description are in the language of the shop session (default language otherwise). The
protected flag and the dates are not exposed. The description is HTML restricted to a few tags and
attributes (descriptions stored by the 2.x line are cleaned on upgrade and on activation); a front that
prints it should still escape or sanitize it.

### Front theme

The module answers the `product.bottom` theme hook (`theme_hook('product.bottom', {product: product})`,
called by Flexy on the product page): it prints the title and the description of the status, and nothing
for the `normal` status. It reads the status from the `ProductStatusAddon` of the product resource when
the theme passes it. The CSS modifier `product-status--<code>` is the code reduced to a slug. Override `templates/theme-hook/product_status.html.twig` to restyle it.

### Back-office

Statuses are edited in the back-office edit language, chosen with the language switcher of the
configuration screen (`edit_language_id`, then the edit language kept in the admin session), never in the
language of the admin interface. The edit forms show what is stored in
that language only, empty when a status has no translation there.

## Changes in 3.0.0

- Thelia 3 only (PHP 8.3 or later). Smarty templates, loops and `routing.xml` are gone.
- The `product_status` and `product_product_status` loops are not ported, nor their filtering of
  products by status (`product_status_code` / `product_status_id` on the product loop). Read the status
  through the API (`/api/front/products`) or `ProductStatusService`.
- The front banner of the `product.bottom` hook is now a theme hook; the 2.x script that moved it and
  removed the restocking alert button was specific to one theme and is not ported.
- One status per product (unique index, see Installation).

## Tests

Integration tests, run from the root of the Thelia project against its test database:

```
php bin/test-prepare
vendor/bin/phpunit --bootstrap vendor/thelia/modules/ProductStatus/Tests/bootstrap.php vendor/thelia/modules/ProductStatus/Tests
```
