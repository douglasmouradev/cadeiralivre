<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Models\TenantModel;

final class MediaController extends Controller
{
    public function tenantLogo(string $slug): Response
    {
        $t = (new TenantModel())->findBySlug($slug);
        if ($t === null || empty($t['logo_path'])) {
            return Response::html('', 404);
        }
        $rel = (string) $t['logo_path'];
        $rel = str_replace(['..', '\\'], '', $rel);
        $path = $this->app->root() . '/storage/uploads/' . $rel;
        $mime = str_ends_with($path, '.png') ? 'image/png' : (str_ends_with($path, '.webp') ? 'image/webp' : 'image/jpeg');

        return Response::file($path, $mime);
    }

    public function tenantCover(string $slug): Response
    {
        $t = (new TenantModel())->findBySlug($slug);
        if ($t === null || empty($t['cover_path'])) {
            return Response::html('', 404);
        }
        $rel = str_replace(['..', '\\'], '', (string) $t['cover_path']);
        $path = $this->app->root() . '/storage/uploads/' . $rel;

        return Response::file($path, $this->imageMime($path));
    }

    public function userAvatar(): Response
    {
        $rel = trim((string) ($this->request->query()['f'] ?? ''));
        $rel = str_replace(['..', '\\'], '', $rel);
        if ($rel === '' || !str_starts_with($rel, 'avatars/')) {
            return Response::html('', 404);
        }
        $path = $this->app->root() . '/storage/uploads/' . $rel;
        if (!is_file($path)) {
            return Response::html('', 404);
        }

        return Response::file($path, $this->imageMime($path));
    }

    private function imageMime(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/jpeg',
        };
    }
}
