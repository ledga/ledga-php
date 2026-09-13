<?php

declare(strict_types=1);

namespace Ledga\Api\Resources;

use Ledga\Api\Enums\AccountSetMemberType;

final readonly class AccountSetMember implements ResourceInterface
{
    public function __construct(
        public AccountSetMemberType $memberType,
        public string $memberId,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): static
    {
        return new self(
            memberType: AccountSetMemberType::from($data['member_type']),
            memberId: $data['member_id'],
        );
    }
}
