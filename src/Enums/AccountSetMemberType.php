<?php

declare(strict_types=1);

namespace Ledga\Api\Enums;

enum AccountSetMemberType: string
{
    case Account = 'account';
    case AccountSet = 'account_set';
}
