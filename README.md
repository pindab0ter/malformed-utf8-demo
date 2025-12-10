# Fixing Malformed UTF-8 Characters in Laravel

This repository demonstrates how to fix the "Malformed UTF-8 characters, possibly incorrectly
encoded" exception in Laravel when working with binary data like UUIDs from packages such as
[`michaeldyrynda/laravel-model-uuid`](https://github.com/michaeldyrynda/laravel-model-uuid).

Read the full blog post:
[Fixing “Malformed UTF-8 characters” in Laravel](https://hansvl.nl/blog/2025-12-10-fixing-malformed-utf-8-characters-exceptions-in-laravel/)

## The Problem

When byte strings (arbitrary byte data stored as strings) are involved in database operations,
Laravel’s exception page can fail to render because it tries to interpret these binary values as
UTF-8, which they’re not. Instead of seeing your actual exception, you get a secondary exception
about malformed UTF-8 characters, and your original error is lost.

## The Solution

This repository showcases three fixes, with each fix in its own commit:

1. Sanitize database error messages
2. Sanitize the Queries section
3. Treat the custom connection as a vendor

You can check out each commit to see the tests fail and pass as the fixes are applied:

```bash
git checkout <commit-sha>
composer dump-autoload # To pick up on app/helpers.php
php artisan test
```

## Files

- [`app/Database/ErrorSanitizingSqlLiteConnection.php`](app/Database/ErrorSanitizingSqlLiteConnection.php)
  Custom connection that sanitizes bindings before throwing exceptions
- [`app/Exceptions/Renderer/Renderer.php`](app/Exceptions/Renderer/Renderer.php) Custom exception
  renderer that uses our custom [`Exception`](app/Exceptions/Renderer/Exception.php)
- [`app/Exceptions/Renderer/Exception.php`](app/Exceptions/Renderer/Exception.php) Custom exception
  that sanitizes query bindings and uses our
  [`ConfigurableFrame`](app/Exceptions/Renderer/ConfigurableFrame.php)
- [`app/Exceptions/Renderer/ConfigurableFrame.php`](app/Exceptions/Renderer/ConfigurableFrame.php)
  Custom frame class that marks any frame with a class listed in
  `config('app.classes_treated_as_from_vendor')` as a vendor frame
- [`app/helpers.php`](app/helpers.php) Contains `sanitize_bindings()` helper that replaces any
  non-UTF8 bindings with hex representations
- [`tests/Feature/MalformedUtf8SanitizationTest.php`](tests/Feature/MalformedUtf8SanitizationTest.php)
  Tests demonstrating each fix
