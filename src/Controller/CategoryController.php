<?php

namespace App\Controller;

use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    use MonthTrait;

    #[Route('/categories', name: 'app_category_recap', methods: [Request::METHOD_GET])]
    public function recap(ExpenseRepository $expenses, #[MapQueryParameter] ?string $month = null): Response
    {
        $month = $this->resolveMonth($month, $expenses);
        $rows = $expenses->totalsByCategory($month);

        $totalCents = array_sum(array_map(static fn (array $row): int => ExpenseRepository::toCents($row['total']), $rows));
        foreach ($rows as &$row) {
            $row['share'] = $totalCents > 0 ? 100 * ExpenseRepository::toCents($row['total']) / $totalCents : 0;
        }
        unset($row);

        return $this->render('category/recap.html.twig', [
            'month' => $month,
            'rows' => $rows,
            'total' => ExpenseRepository::format($totalCents),
            'expense_count' => array_sum(array_column($rows, 'count')),
        ]);
    }
}
