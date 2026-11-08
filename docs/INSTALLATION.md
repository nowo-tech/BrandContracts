# Installation

```bash
composer require nowo-tech/brand-contracts
```

The package is a plain PHP library: there is no bundle to register and no configuration file. Consumers (UiKit, Pwa,
MailKit, LegalKit, SeoKit, host renderers) type-hint its interfaces; a provider (InstanceSettings, or a small host
class) implements `BrandProviderInterface`.

Requirements: PHP `>=8.2 <8.6` with `mbstring`, and `symfony/translation-contracts` ^3.4.
