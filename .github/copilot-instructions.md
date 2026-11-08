## AI contribution guidelines (Nowo PHP library)

These instructions apply to AI-assisted contributions (GitHub Copilot, Cursor, Claude and similar tools).

### Project context

- This is a **framework-free PHP library** published as `nowo-tech/brand-contracts` on Packagist.
- It holds the shared brand contract (interfaces and value objects) read by nowo-tech Symfony bundles and host apps.
- Respect the **PHP** range declared in `composer.json` (`>=8.2 <8.6`).
- The only runtime dependency besides PHP extensions is `symfony/translation-contracts`. Do not add others.

### Rules

- Keep the public API stable: this package is meant to stay on 1.x for years.
- Value objects are `final readonly` and validate in their constructor.
- Every public class and method has an English PHPDoc block.
- Tests keep coverage at 100% (`composer test-coverage-100`).
- Never add AI co-author trailers to commit messages (REQ-GIT-001).
