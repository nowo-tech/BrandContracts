<?php

declare(strict_types=1);

namespace Nowo\Brand\Contract;

use InvalidArgumentException;

use function is_string;
use function preg_match;
use function sprintf;

/**
 * Whose brand to resolve when there is no ambient context: a project, tenant or site public id (DDD P2, 1.1).
 *
 * A Messenger handler or a console command that renders for one project passes its scope to
 * {@see ScopedBrandProviderInterface::getBrandFor()} instead of setting request or tenant state first. The id is
 * opaque to this package: the host decides what it means. It must be printable ASCII without whitespace, 1 to
 * {@see self::MAX_ID_LENGTH} characters (a UUID, ULID, slug or numeric id fits), so it is safe in logs. It may still
 * contain `/ \ : { } ( ) @` and `../`: hash or encode it (`hash('xxh128', $id)`, `rawurlencode()`) before using it in
 * a PSR-6 cache key or a file path.
 *
 * The scope selects, it does not authorize. Derive it from trusted state: the project of a message your application
 * created, a console option run by an operator, or the route owner after your access check. Never build it from raw
 * request input. Ids are compared as given (case-sensitive); normalise them the way your store does.
 */
final readonly class BrandScope
{
    /** Longest id accepted. */
    public const MAX_ID_LENGTH = 191;

    private const ID_PATTERN = '/^[\x21-\x7e]{1,191}\z/';

    /** The project, tenant or site public id. */
    public string $id;

    /**
     * @throws InvalidArgumentException when the id is empty, too long, or has whitespace or non-ASCII characters
     */
    public function __construct(string $id)
    {
        if (preg_match(self::ID_PATTERN, $id) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid brand scope id: expected 1 to %d printable ASCII characters without whitespace.', self::MAX_ID_LENGTH));
        }

        $this->id = $id;
    }

    /**
     * Same id.
     *
     * @return array{id: string}
     */
    public function __serialize(): array
    {
        return ['id' => $this->id];
    }

    /**
     * Re-validates the id, so a crafted serialized value cannot bypass the constructor rules.
     *
     * @param array<array-key, mixed> $data
     *
     * @throws InvalidArgumentException when the id is missing or invalid
     */
    public function __unserialize(array $data): void
    {
        $id = $data['id'] ?? null;
        if (!is_string($id) || preg_match(self::ID_PATTERN, $id) !== 1) {
            throw new InvalidArgumentException(sprintf('Invalid brand scope id: expected 1 to %d printable ASCII characters without whitespace.', self::MAX_ID_LENGTH));
        }

        $this->id = $id;
    }

    /**
     * Same id.
     */
    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
