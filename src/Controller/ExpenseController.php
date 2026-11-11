<?php

namespace App\Controller;

use App\Entity\Expense;
use App\Enum\ExpenseStatus;
use App\Form\ExpenseType;
use App\Repository\ExpenseRepository;
use App\Service\ReceiptStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

final class ExpenseController extends AbstractController
{
    use MonthTrait;

    public function __construct(
        private readonly ExpenseRepository $expenses,
        private readonly EntityManagerInterface $entityManager,
        private readonly ReceiptStorage $receipts,
    ) {
    }

    #[Route('/', name: 'app_home', methods: [Request::METHOD_GET])]
    public function home(): Response
    {
        return $this->redirectToRoute('app_expense_index');
    }

    /** The month list (root screen of the app). */
    #[Route('/expenses', name: 'app_expense_index', methods: [Request::METHOD_GET])]
    public function index(#[MapQueryParameter] ?string $month = null): Response
    {
        $month = $this->resolveMonth($month, $this->expenses);
        $expenses = $this->expenses->findForMonth($month);

        $counts = ['draft' => 0, 'submitted' => 0, 'reimbursed' => 0];
        foreach ($expenses as $expense) {
            ++$counts[$expense->getStatus()->value];
        }

        return $this->render('expense/index.html.twig', [
            'month' => $month,
            'expenses' => $expenses,
            'total' => ExpenseRepository::sum($expenses),
            'counts' => $counts,
            'previous_month' => $this->expenses->hasExpensesBefore($month) ? $month->modify('first day of previous month') : null,
            'next_month' => $this->expenses->hasExpensesAfter($month) ? $month->modify('first day of next month') : null,
        ]);
    }

    #[Route('/expenses/new', name: 'app_expense_new', methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function new(Request $request): Response
    {
        $expense = (new Expense())
            ->setSpentAt(new \DateTimeImmutable('today'))
            ->setStatus(ExpenseStatus::Draft);

        $form = $this->createForm(ExpenseType::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $expense->setReceipt($this->receipts->store($form->get('receipt')->getData()));
            $this->entityManager->persist($expense);
            $this->entityManager->flush();

            $this->addFlash('success', 'Expense saved.');

            return $this->redirectToRoute('app_expense_index', ['month' => $expense->getSpentAt()->format('Y-m')], Response::HTTP_SEE_OTHER);
        }

        // An invalid submitted form is rendered with a 422 status, which Turbo needs.
        return $this->render('expense/new.html.twig', ['form' => $form]);
    }

    #[Route('/expenses/{id}', name: 'app_expense_show', requirements: ['id' => '\d+'], methods: [Request::METHOD_GET])]
    public function show(Expense $expense): Response
    {
        return $this->render('expense/show.html.twig', ['expense' => $expense]);
    }

    /** Same form, pre-filled. A new photo replaces the old one. */
    #[Route('/expenses/{id}/edit', name: 'app_expense_edit', requirements: ['id' => '\d+'], methods: [Request::METHOD_GET, Request::METHOD_POST])]
    public function edit(Request $request, Expense $expense): Response
    {
        $form = $this->createForm(ExpenseType::class, $expense, ['require_receipt' => null === $expense->getReceipt()]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('receipt')->getData();
            if ($file instanceof UploadedFile) {
                if (null !== $old = $expense->getReceipt()) {
                    $this->receipts->remove($old);
                }
                $expense->setReceipt($this->receipts->store($file));
            }
            $this->entityManager->flush();

            $this->addFlash('success', 'Expense updated.');

            return $this->redirectToRoute('app_expense_show', ['id' => $expense->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('expense/edit.html.twig', ['form' => $form, 'expense' => $expense]);
    }

    /** Draft → Submitted, from the detail screen. A plain POST form: same code on the web and in the app. */
    #[Route('/expenses/{id}/submit', name: 'app_expense_submit', requirements: ['id' => '\d+'], methods: [Request::METHOD_POST])]
    public function submit(Request $request, Expense $expense): Response
    {
        if (!$this->isCsrfTokenValid('submit-expense-'.$expense->getId(), $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        if ($expense->canBeSubmitted()) {
            $expense->submit();
            $this->entityManager->flush();
            $this->addFlash('success', 'Expense submitted.');
        }

        return $this->redirectToRoute('app_expense_show', ['id' => $expense->getId()], Response::HTTP_SEE_OTHER);
    }
}
