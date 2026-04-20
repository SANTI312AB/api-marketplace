<?php 

// src/Service/QrCodeGenerator.php
namespace App\Service;

use Endroid\QrCode\Builder\BuilderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\KernelInterface;

class QrCodeGenerator
{
    private BuilderInterface $builder;
    private Filesystem $filesystem;
    private string $publicDir;

    public function __construct(
        BuilderInterface $builder,
        Filesystem $filesystem,
        KernelInterface $kernel
    ) {
        $this->builder = $builder;
        $this->filesystem = $filesystem;
        
        // Obtiene la ruta del directorio público dinámicamente
        $this->publicDir = $kernel->getProjectDir().'/public';
    }

    public function generateAndSave(string $data, string $filename): string
    {
        $qrDirectory = "{$this->publicDir}/qr_codes/";
        $this->filesystem->mkdir($qrDirectory);
        
        $filePath = "{$qrDirectory}{$filename}";

        if (!$this->filesystem->exists($filePath)) {
            $qrCode = $this->builder->build(
                data: $data,
            );
            
            $qrCode->saveToFile($filePath);
        }

        return $filename;
    }
}