<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;

final class HomeController extends Controller
{
    public function index(): Response
    {
        if (!empty($_SESSION['user_id'])) {
            return Response::redirect($this->postAuthHomePath());
        }

        return Response::redirect('/login');
    }
}
