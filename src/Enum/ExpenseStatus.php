<?php

namespace App\Enum;

enum ExpenseStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case Reimbursed = 'reimbursed';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Submitted => 'Submitted',
            self::Reimbursed => 'Reimbursed',
        };
    }
}
