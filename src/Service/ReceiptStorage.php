<?php

namespace App\Service;

use App\Entity\Receipt;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores receipt photos in public/uploads/receipts/ — served directly, no network dependency on stage.
 */
final class ReceiptStorage
{
    public function __construct(
        #[Autowire('%kernel.project_dir%/public/uploads/receipts')]
        private readonly string $directory,
        private readonly Filesystem $filesystem,
    ) {
    }

    public function store(UploadedFile $file): Receipt
    {
        $filename = $this->newName($file->guessExtension() ?? 'jpg');
        $file->move($this->directory, $filename);

        return new Receipt($filename);
    }

    /** Used by the fixtures: copies a file from the repository into the uploads folder. */
    public function import(string $path): Receipt
    {
        $filename = $this->newName(pathinfo($path, \PATHINFO_EXTENSION) ?: 'png');
        $this->filesystem->copy($path, $this->directory.'/'.$filename);

        return new Receipt($filename);
    }

    public function remove(Receipt $receipt): void
    {
        $this->filesystem->remove($this->directory.'/'.$receipt->getFilename());
    }

    private function newName(string $extension): string
    {
        return bin2hex(random_bytes(8)).'.'.strtolower($extension);
    }
}
