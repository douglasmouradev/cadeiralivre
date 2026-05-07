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
}
