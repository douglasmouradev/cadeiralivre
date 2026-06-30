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
            'description' => 'Como o ' . app_name() . ' trata dados pessoais de lojas e clientes.',
        ]);
    }

    public function terms(): Response
    {
        return $this->view('legal/terms', [
            'title' => 'Termos de uso',
            'description' => 'Termos de uso da plataforma ' . app_name() . '.',
        ]);
    }

    public function lgpd(): Response
    {
        return $this->view('legal/lgpd', [
            'title' => 'LGPD — direitos do titular',
            'description' => 'Seus direitos sob a LGPD no ' . app_name() . '.',
        ]);
    }
}
