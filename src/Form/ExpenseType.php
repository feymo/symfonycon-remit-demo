<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Expense;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Field names match templates/expense/_form.html.twig.
 * Validation messages are short, readable from the back of the room.
 */
final class ExpenseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('receipt', FileType::class, [
                'mapped' => false,
                'required' => $options['require_receipt'],
                'constraints' => array_filter([
                    $options['require_receipt'] ? new Assert\NotNull(message: 'A receipt photo is required.') : null,
                    new Assert\Image(maxSize: '12M', mimeTypesMessage: 'The receipt must be a photo.'),
                ]),
            ])
            // html5: type="number" — iOS shows the decimal keypad and always sends "12.50",
            // even on a French iPhone that displays "12,50".
            ->add('amount', NumberType::class, [
                'input' => 'string',
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01', 'min' => '0'],
                'constraints' => [
                    new Assert\NotBlank(message: 'Enter an amount greater than €0.00.'),
                    new Assert\Positive(message: 'Enter an amount greater than €0.00.'),
                ],
            ])
            ->add('spentAt', DateType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'constraints' => [new Assert\NotNull(message: 'Pick the date of the expense.')],
            ])
            ->add('merchant', TextType::class, [
                'constraints' => [new Assert\NotBlank(message: 'Enter the merchant’s name.')],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'label',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('c')->orderBy('c.position', 'ASC'),
                'expanded' => true,
                'constraints' => [new Assert\NotNull(message: 'Pick a category.')],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Expense::class,
            'require_receipt' => true,   // false on edit when a receipt is already attached
        ]);
        $resolver->setAllowedTypes('require_receipt', 'bool');
    }
}
