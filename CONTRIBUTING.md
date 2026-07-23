# Contributing to WrapSplashPHP

Thank you for considering contributing to WrapSplashPHP! This document provides guidelines and instructions for contributing.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for everyone.

## How to Contribute

### Reporting Bugs

- Check the [existing issues](https://github.com/SandeepVattapparambil/wrapsplash-php/issues) to avoid duplicates.
- Open a new issue using the **Bug Report** template.
- Include a clear description, steps to reproduce, expected vs actual behavior, and your environment (PHP version, OS, etc.).

### Suggesting Features

- Open a new issue using the **Feature Request** template.
- Describe the use case and how it benefits the project.

### Submitting Changes

1. Fork the repository.
2. Create a new branch from `main`:
   ```sh
   git checkout -b feature/my-feature
   ```
3. Install dependencies:
   ```sh
   composer install
   ```
4. Make your changes following the code style below.
5. Run the test suite:
   ```sh
   vendor/bin/phpunit
   ```
6. Commit with a clear message:
   ```sh
   git commit -m "Add: description of change"
   ```
7. Push and open a Pull Request:
   ```sh
   git push origin feature/my-feature
   ```

## Development Setup

### Requirements

- PHP 8.1+
- [Composer](https://getcomposer.org/)
- An Unsplash developer account for testing API calls

### Running Tests

```sh
composer install
vendor/bin/phpunit
```

### Code Style

- Follow [PSR-12](https://www.php-fig.org/psr/psr-12/) coding standards.
- Use strict typing: `declare(strict_types=1);`
- Write docblocks for public methods.
- Keep methods focused and concise.

### Project Structure

```
src/
  Config/           # API endpoint definitions
  Enums/            # Enumerations (PhotoOrder, PhotoOrientation, HttpMethod)
  Configuration.php # Configuration data class
  WrapSplash.php    # Main API wrapper class
  WrapSplashException.php  # Custom exception class
tests/
  WrapSplashTest.php  # PHPUnit test suite
```

## Pull Request Guidelines

- Keep PRs focused on a single change.
- Include tests for new functionality.
- Update documentation (README.md) if applicable.
- Ensure all tests pass before submitting.
- Reference related issues (e.g., `Fixes #12`).

## Release Process

1. Update version in `composer.json`.
2. Update `CHANGELOG.md` with changes.
3. Tag the release:
   ```sh
   git tag v1.0.0
   git push origin v1.0.0
   ```

## Questions?

Open an issue or reach out to the maintainer.
