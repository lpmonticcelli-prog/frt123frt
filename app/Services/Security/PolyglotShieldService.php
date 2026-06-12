<?php

declare(strict_types=1);

namespace App\Services\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class PolyglotShieldService
{
    /**
     * ZT-DEFENSE: Escudo Anti-RCE / Anti-Polyglot.
     * Desmonta e remonta arquivos binários na RAM para expurgar payloads (ex: PHP/Shellcode)
     * escondidos em metadados EXIF ou chunks de imagens, neutralizando ataques LFI.
     */
    public function sanitizeAndStore(UploadedFile $file, string $pathPrefix): string
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        $this->verifyMagicBytes($file->getRealPath(), $extension);

        $filename = hash('sha256', Str::uuid() . microtime(true)) . '.' . $extension;
        $fullPath = rtrim($pathPrefix, '/') . '/' . $filename;

        if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
            $this->rebuildImage($file->getRealPath(), $extension, $fullPath);
        } elseif ($extension === 'pdf') {
            Storage::put($fullPath, file_get_contents($file->getRealPath()));
        } else {
            throw new Exception('Extensão não homologada pela malha de segurança.');
        }

        return $fullPath;
    }

    private function verifyMagicBytes(string $filePath, string $extension): void
    {
        $fileH = fopen($filePath, 'rb');
        $bytes = bin2hex(fread($fileH, 4));
        fclose($fileH);

        $signatures = [
            'jpg'  => ['ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffe3', 'ffd8ffe8'],
            'jpeg' => ['ffd8ffe0', 'ffd8ffe1', 'ffd8ffe2', 'ffd8ffe3', 'ffd8ffe8'],
            'png'  => ['89504e47'],
            'pdf'  => ['25504446'],
        ];

        if (!isset($signatures[$extension]) || !in_array(substr($bytes, 0, 8), $signatures[$extension], true)) {
            throw new Exception('Assinatura binária corrompida. Vetor de intrusão bloqueado.');
        }
    }

    private function rebuildImage(string $sourcePath, string $extension, string $destinationPath): void
    {
        $image = match ($extension) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($sourcePath),
            'png' => @imagecreatefrompng($sourcePath),
            default => false,
        };

        if (!$image) {
            throw new Exception('Falha ao instanciar o buffer de imagem. Entropia maliciosa detectada.');
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $cleanImage = imagecreatetruecolor($width, $height);

        if ($extension === 'png') {
            imagealphablending($cleanImage, false);
            imagesavealpha($cleanImage, true);
            $transparent = imagecolorallocatealpha($cleanImage, 255, 255, 255, 127);
            imagefilledrectangle($cleanImage, 0, 0, $width, $height, $transparent);
        }

        imagecopyresampled($cleanImage, $image, 0, 0, 0, 0, $width, $height, $width, $height);

        ob_start();
        if ($extension === 'png') {
            imagepng($cleanImage, null, 9);
        } else {
            imagejpeg($cleanImage, null, 85);
        }
        $cleanContent = ob_get_clean();

        imagedestroy($image);
        imagedestroy($cleanImage);

        if (!Storage::put($destinationPath, $cleanContent)) {
            throw new Exception('Falha de I/O na persistência do binário higienizado.');
        }
    }
}