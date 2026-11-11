<?php

namespace App\Controller;

use App\Repository\ExpenseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ArchiveController extends AbstractController
{
    /**
     * The six months before the current one.
     * Opens as a modal in the app (rule in App\Native\AppNativeConfiguration), same template.
     */
    #[Route('/archives', name: 'app_archive_index', methods: [Request::METHOD_GET])]
    public function index(ExpenseRepository $expenses): Response
    {
        $current = $expenses->findLatestMonth() ?? new \DateTimeImmutable('first day of this month midnight');
        $months = $expenses->monthlyHistory($current);

        return $this->render('archive/index.html.twig', [
            'months' => $months,
            'total' => ExpenseRepository::format(array_sum(array_map(static fn (array $m): int => ExpenseRepository::toCents($m['total']), $months))),
            'expense_count' => array_sum(array_column($months, 'count')),
        ]);
    }
}
