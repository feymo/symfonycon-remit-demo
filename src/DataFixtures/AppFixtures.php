<?php

namespace App\DataFixtures;

use App\Entity\Category;
use App\Entity\Expense;
use App\Enum\ExpenseStatus;
use App\Service\ReceiptStorage;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The demo data: the Warsaw trip (November 2026, with receipt photos) and six quieter months for the archives.
 *
 *   php bin/console doctrine:fixtures:load -n
 */
final class AppFixtures extends Fixture
{
    private const CATEGORIES = [
        'flights' => ['Flights', 'plane'],
        'lodging' => ['Lodging', 'bed'],
        'transport' => ['Transport', 'car'],
        'meals' => ['Meals', 'utensils'],
        'coffee' => ['Coffee', 'cup'],
        'supplies' => ['Supplies', 'box'],
    ];

    /** [date, merchant, category, amount, status, receipt file in fixtures/receipts/] — total €688.01 */
    private const WARSAW_TRIP = [
        ['2026-11-03', 'Skyroute Travel', 'flights', '287.40', 'reimbursed', 'skyroute-travel.png'],
        ['2026-11-18', 'Warsaw Airport Taxi', 'transport', '14.82', 'submitted', 'warsaw-airport-taxi.png'],
        ['2026-11-19', 'Kawiarnia Nowy Świat', 'coffee', '3.96', 'draft', 'kawiarnia-nowy-swiat.png'],
        ['2026-11-19', 'Bar Mleczny Mokotów', 'meals', '9.34', 'draft', 'bar-mleczny-mokotow.png'],
        ['2026-11-19', 'Bistro Nowe Miasto', 'meals', '46.70', 'submitted', 'bistro-nowe-miasto.png'],
        ['2026-11-20', 'Hotel Vistula Old Town', 'lodging', '318.60', 'submitted', 'hotel-vistula-old-town.png'],
        ['2026-11-20', 'Rubber Duck Emporium', 'supplies', '7.19', 'draft', 'rubber-duck-emporium.png'],
    ];

    /** Previous months, shown in the archives. */
    private const HISTORY = [
        // October 2026 — €412.35, 1 pending
        ['2026-10-06', 'Parking Mériadeck', 'transport', '18.40', 'reimbursed'],
        ['2026-10-09', 'Le Comptoir des Chartrons', 'meals', '64.90', 'reimbursed'],
        ['2026-10-14', 'Papeterie Mériadeck', 'supplies', '37.85', 'reimbursed'],
        ['2026-10-21', 'Atlantique Rail', 'transport', '189.00', 'reimbursed'],
        ['2026-10-22', 'Hôtel des Arts, Paris', 'lodging', '102.20', 'submitted'],
        // September 2026 — €1,086.20
        ['2026-09-03', 'Atlantique Rail', 'transport', '132.60', 'reimbursed'],
        ['2026-09-03', 'Café des Halles, Lille', 'coffee', '4.20', 'reimbursed'],
        ['2026-09-03', 'Hôtel Grand Place, Lille', 'lodging', '298.00', 'reimbursed'],
        ['2026-09-04', 'Brasserie du Beffroi', 'meals', '41.30', 'reimbursed'],
        ['2026-09-04', 'Taxi Lille Métropole', 'transport', '22.70', 'reimbursed'],
        ['2026-09-12', 'Skyroute Travel', 'flights', '389.90', 'reimbursed'],
        ['2026-09-17', 'Papeterie Mériadeck', 'supplies', '54.80', 'reimbursed'],
        ['2026-09-24', 'Le Comptoir Bordelais', 'meals', '78.50', 'reimbursed'],
        ['2026-09-25', 'Parking Mériadeck', 'transport', '64.20', 'reimbursed'],
        // August 2026 — €96.40
        ['2026-08-26', 'Papeterie Mériadeck', 'supplies', '58.90', 'reimbursed'],
        ['2026-08-28', 'Chez Fernand', 'meals', '37.50', 'reimbursed'],
        // July 2026 — €238.75
        ['2026-07-02', 'Atlantique Rail', 'transport', '96.40', 'reimbursed'],
        ['2026-07-02', 'Brasserie de l’Opéra', 'meals', '52.35', 'reimbursed'],
        ['2026-07-02', 'Café des Arts', 'coffee', '5.50', 'reimbursed'],
        ['2026-07-03', 'Hôtel des Arts, Paris', 'lodging', '84.50', 'reimbursed'],
        // June 2026 — €151.10
        ['2026-06-11', 'Papeterie Mériadeck', 'supplies', '89.90', 'reimbursed'],
        ['2026-06-18', 'Le Comptoir des Chartrons', 'meals', '54.60', 'reimbursed'],
        ['2026-06-18', 'Parking Mériadeck', 'transport', '6.60', 'reimbursed'],
        // May 2026 — €327.90
        ['2026-05-05', 'Atlantique Rail', 'transport', '118.20', 'reimbursed'],
        ['2026-05-05', 'Hôtel des Arts, Paris', 'lodging', '142.00', 'reimbursed'],
        ['2026-05-06', 'Café des Arts', 'coffee', '3.80', 'reimbursed'],
        ['2026-05-06', 'Bistrot Montorgueil', 'meals', '39.70', 'reimbursed'],
        ['2026-05-06', 'Taxi Parisien', 'transport', '17.40', 'reimbursed'],
        ['2026-05-20', 'Papeterie Mériadeck', 'supplies', '6.80', 'reimbursed'],
    ];

    public function __construct(
        private readonly ReceiptStorage $receipts,
        #[Autowire('%kernel.project_dir%/fixtures/receipts')]
        private readonly string $receiptsSource,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        $categories = [];
        $position = 0;
        foreach (self::CATEGORIES as $key => [$label, $icon]) {
            $manager->persist($categories[$key] = new Category($label, $icon, $position++));
        }

        foreach (self::WARSAW_TRIP as [$date, $merchant, $category, $amount, $status, $file]) {
            $expense = $this->expense($date, $merchant, $categories[$category], $amount, $status);
            $expense->setReceipt($this->receipts->import($this->receiptsSource.'/'.$file));
            $manager->persist($expense);
        }

        foreach (self::HISTORY as [$date, $merchant, $category, $amount, $status]) {
            $manager->persist($this->expense($date, $merchant, $categories[$category], $amount, $status));
        }

        $manager->flush();
    }

    private function expense(string $date, string $merchant, Category $category, string $amount, string $status): Expense
    {
        return (new Expense())
            ->setSpentAt(new \DateTimeImmutable($date))
            ->setMerchant($merchant)
            ->setCategory($category)
            ->setAmount($amount)
            ->setStatus(ExpenseStatus::from($status));
    }
}
