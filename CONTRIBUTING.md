# Contributing to Quinrel for Laravel

Thank you for considering contributing to `quinrel/laravel`!

## Code of Conduct

Please be respectful and constructive in all discussions, issues, and pull requests.

## Development Workflow

### 1. Requirements
- PHP 8.2 or higher
- Composer 2.x

### 2. Setup
Clone the repository and install dependencies:

```bash
git clone https://github.com/quinrel/laravel.git
cd laravel
composer install
```

### 3. Code Style & Quality Standards
All code must adhere to PSR-12 and modern Laravel conventions. Before submitting a pull request, run:

```bash
# Code style formatting (Laravel Pint)
composer format

# Static analysis at Level 8 (PHPStan / Larastan)
composer analyse

# Run the full automated test suite (PHPUnit)
composer test
```

### 4. Guidelines
- **Strict Typing:** All new PHP files must begin with `declare(strict_types=1);`.
- **Laravel First:** Prefer Laravel native components (Service Providers, Http Client, Cache, Queue, Events, Blade, Artisan) over custom abstractions.
- **Zero Unverified Code:** Avoid empty implementations, TODO placeholders, or broad catch blocks that swallow errors.
- **Test Coverage:** Every bug fix must include a regression test; every new feature must include feature or unit tests covering success, edge, and failure paths.

## Submitting Pull Requests

1. Fork the repository and create your branch from `main`.
2. Ensure all tests and static analysis pass cleanly without errors or suppressions.
3. Keep pull requests focused on a single change or feature.
4. Update `CHANGELOG.md` following [Keep a Changelog](https://keepachangelog.com/en/1.0.0/) standards.

