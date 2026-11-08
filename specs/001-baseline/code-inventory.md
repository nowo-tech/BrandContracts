# Code inventory (baseline)

Every production file under `src/` is listed here (constitution principle III).

| File | Role | Spec |
| --- | --- | --- |
| `src/BrandAssetSlot.php` | Slot names (5 fixed + `extra.<id>`), validation helpers | FR-001 |
| `src/BrandProviderInterface.php` | Provider seam | FR-002 |
| `src/Brand.php` | Brand value object, defaults, fallback chain, version | FR-003, FR-004, FR-005 |
| `src/BrandColors.php` | Seven hex colours of one mode | FR-006 |
| `src/BrandAsset.php` | One resolved image, URL and type rules, inline safety | FR-007 |
| `src/BrandAssetOrigin.php` | `Uploaded` / `ConfigDefault` | FR-007 |
| `src/BrandAssetReaderInterface.php` | Bytes of an asset or variant | FR-008 |
| `src/BrandAssetReadException.php` | Reader failure | FR-008 |
| `src/BrandAssetDataUri.php` | Data URI / media type from sniffed bytes | FR-008 |
| `src/ColorMath.php` | Contrast, mixes, shades, contrast warnings | FR-009 |
