<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

final class LegalController extends Controller
{
    public function privacy(): Response
    {
        return $this->view('legal/privacy', [
            'title' => 'Política de privacidade',
        ]);
    }

    public function terms(): Response
    {
        return $this->view('legal/terms', [
            'title' => 'Termos de uso',
        ]);
    }

    public function lgpd(): Response
    {
        return $this->view('legal/lgpd', [
            'title' => 'LGPD — direitos do titular',
        ]);
    }
}
