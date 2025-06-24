# PHP 7.4 Anonymizer

This library provides simple tools to mask sensitive information in arrays and objects.

## Installation

```bash
composer require example/php-anonymizer
```

## Usage

```php
use Anonymizer\Anonymizer;

$anonymizer = (new Anonymizer())
    ->configPartialFields(['name'])     // "John" -> "Jo*n"
    ->configFullFields(['password'])    // replaced with "********"
    ->configRegexMatch(Anonymizer::REGEX_EMAIL);

$data = [
    'name' => 'Robert',
    'password' => 'secret',
    'email' => 'user@example.com',
];

$result = $anonymizer->anonymize($data);
```

The library recursively processes arrays and objects. If a circular reference is
encountered in objects, a `CircularReferenceException` is thrown.
