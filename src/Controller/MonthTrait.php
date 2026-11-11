<?php

namespace App\Controller;

use App\Repository\ExpenseRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Resolves ?month=YYYY-MM. Without it, the app opens on the month of the latest expense,
 * so the demo always lands on the Warsaw trip whatever the date of the rehearsal.
 */
trait MonthTrait
{
    private function resolveMonth(?string $month, ExpenseRepository $expenses): \DateTimeImmutable
    {
        if (null === $month) {
            return $expenses->findLatestMonth() ?? new \DateTimeImmutable('first day of this month midnight');
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m', $month);
        if (false === $date || $date->format('Y-m') !== $month) {
            throw new NotFoundHttpException(\sprintf('Unknown month "%s".', $month));
        }

        return $date;
    }
}
