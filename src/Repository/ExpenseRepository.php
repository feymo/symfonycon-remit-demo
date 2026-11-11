<?php

namespace App\Repository;

use App\Entity\Expense;
use App\Enum\ExpenseStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Amounts are summed in cents to avoid float drift (€688.01 must stay €688.01 on the projector).
 *
 * @extends ServiceEntityRepository<Expense>
 */
class ExpenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Expense::class);
    }

    /**
     * @return list<Expense>
     */
    public function findForMonth(\DateTimeImmutable $month): array
    {
        return $this->createQueryBuilder('e')
            ->addSelect('c', 'r')
            ->join('e.category', 'c')
            ->leftJoin('e.receipt', 'r')
            ->where('e.spentAt >= :start AND e.spentAt < :end')
            ->setParameter('start', $month, Types::DATE_IMMUTABLE)
            ->setParameter('end', $month->modify('first day of next month'), Types::DATE_IMMUTABLE)
            ->orderBy('e.spentAt', 'ASC')
            ->addOrderBy('e.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** First day of the month of the most recent expense, or null when the table is empty. */
    public function findLatestMonth(): ?\DateTimeImmutable
    {
        $latest = $this->createQueryBuilder('e')
            ->select('MAX(e.spentAt)')
            ->getQuery()
            ->getSingleScalarResult();

        return null === $latest ? null : (new \DateTimeImmutable($latest))->modify('first day of this month');
    }

    public function hasExpensesBefore(\DateTimeImmutable $month): bool
    {
        return $this->countWhere('e.spentAt < :date', $month) > 0;
    }

    public function hasExpensesAfter(\DateTimeImmutable $month): bool
    {
        return $this->countWhere('e.spentAt >= :date', $month->modify('first day of next month')) > 0;
    }

    /**
     * Totals per category for one month, biggest first.
     *
     * @return list<array{category: \App\Entity\Category, total: string, count: int}>
     */
    public function totalsByCategory(\DateTimeImmutable $month): array
    {
        $rows = $this->getEntityManager()->createQuery(
            'SELECT c AS category, SUM(e.amount) AS total, COUNT(e.id) AS expenses
             FROM App\Entity\Category c JOIN App\Entity\Expense e WITH e.category = c
             WHERE e.spentAt >= :start AND e.spentAt < :end
             GROUP BY c.id
             ORDER BY total DESC'
        )
            ->setParameter('start', $month, Types::DATE_IMMUTABLE)
            ->setParameter('end', $month->modify('first day of next month'), Types::DATE_IMMUTABLE)
            ->getResult();

        return array_map(static fn (array $row): array => [
            'category' => $row['category'],
            'total' => self::format(self::toCents((string) $row['total'])),
            'count' => (int) $row['expenses'],
        ], $rows);
    }

    /**
     * Past months with their total, newest first.
     *
     * @return list<array{month: \DateTimeImmutable, total: string, count: int, pending: int}>
     */
    public function monthlyHistory(\DateTimeImmutable $before, int $limit = 6): array
    {
        $rows = $this->createQueryBuilder('e')
            ->select('e.spentAt', 'e.amount', 'e.status')
            ->where('e.spentAt < :before')
            ->setParameter('before', $before, Types::DATE_IMMUTABLE)
            ->orderBy('e.spentAt', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $months = [];
        foreach ($rows as $row) {
            $key = $row['spentAt']->format('Y-m');
            $months[$key] ??= ['month' => $row['spentAt']->modify('first day of this month'), 'cents' => 0, 'count' => 0, 'pending' => 0];
            $months[$key]['cents'] += self::toCents($row['amount']);
            ++$months[$key]['count'];
            $status = $row['status'] instanceof ExpenseStatus ? $row['status'] : ExpenseStatus::from($row['status']);
            if (ExpenseStatus::Reimbursed !== $status) {
                ++$months[$key]['pending'];
            }
        }

        return array_map(static fn (array $m): array => [
            'month' => $m['month'],
            'total' => self::format($m['cents']),
            'count' => $m['count'],
            'pending' => $m['pending'],
        ], \array_slice(array_values($months), 0, $limit));
    }

    /**
     * @param list<Expense> $expenses
     */
    public static function sum(array $expenses): string
    {
        return self::format(array_sum(array_map(static fn (Expense $e): int => self::toCents((string) $e->getAmount()), $expenses)));
    }

    public static function toCents(string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    public static function format(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    private function countWhere(string $condition, \DateTimeImmutable $date): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where($condition)
            ->setParameter('date', $date, Types::DATE_IMMUTABLE)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
