<?php

declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    public function __construct(
        private readonly string $storageDir,
        private readonly int $maxBytes,
    ) {
    }

    /** @param array<string, mixed> $file from $_FILES[key] */
    public function storeImage(array $file): string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            throw new \InvalidArgumentException('Nenhum arquivo enviado.');
        }
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException('Erro no upload.');
        }
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            throw new \InvalidArgumentException('Upload inválido.');
        }
        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $this->maxBytes) {
            throw new \InvalidArgumentException('Arquivo muito grande.');
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($tmp);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        if (!is_string($mime) || !isset($allowed[$mime])) {
            throw new \InvalidArgumentException('Tipo de imagem não permitido.');
        }
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0755, true);
        }
        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $dest = rtrim($this->storageDir, '/') . '/' . $name;
        if (!move_uploaded_file($tmp, $dest)) {
            throw new \RuntimeException('Não foi possível salvar o arquivo.');
        }

        return $name;
    }
}
