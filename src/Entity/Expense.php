<?php

namespace App\Entity;

use App\Enum\ExpenseStatus;
use App\Repository\ExpenseRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExpenseRepository::class)]
#[ORM\Index(name: 'idx_expense_spent_at', columns: ['spent_at'])]
class Expense
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /** Euros, as a decimal string ("287.40"). */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $amount = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    private ?\DateTimeImmutable $spentAt = null;

    #[ORM\Column(length: 120)]
    private ?string $merchant = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    #[ORM\Column(length: 20, enumType: ExpenseStatus::class)]
    private ExpenseStatus $status = ExpenseStatus::Draft;

    #[ORM\OneToOne(cascade: ['persist', 'remove'], orphanRemoval: true)]
    private ?Receipt $receipt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getSpentAt(): ?\DateTimeImmutable
    {
        return $this->spentAt;
    }

    public function setSpentAt(?\DateTimeImmutable $spentAt): static
    {
        $this->spentAt = $spentAt;

        return $this;
    }

    public function getMerchant(): ?string
    {
        return $this->merchant;
    }

    public function setMerchant(?string $merchant): static
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getStatus(): ExpenseStatus
    {
        return $this->status;
    }

    public function setStatus(ExpenseStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** Only a draft can be sent to accounting. Reimbursed is set on their side (here: the fixtures). */
    public function canBeSubmitted(): bool
    {
        return ExpenseStatus::Draft === $this->status;
    }

    public function submit(): static
    {
        if (!$this->canBeSubmitted()) {
            throw new \LogicException(\sprintf('Only a draft can be submitted, this expense is "%s".', $this->status->value));
        }
        $this->status = ExpenseStatus::Submitted;

        return $this;
    }

    public function getReceipt(): ?Receipt
    {
        return $this->receipt;
    }

    public function setReceipt(?Receipt $receipt): static
    {
        $this->receipt = $receipt;

        return $this;
    }
}
