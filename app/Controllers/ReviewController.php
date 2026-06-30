<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Response;
use App\Helpers\Flash;
use App\Models\ReviewModel;

final class ReviewController extends Controller
{
    public function index(): Response
    {
        $tid = $this->tenantId();
        $reviews = new ReviewModel();

        return $this->view('reviews/index', [
            'title' => 'Avaliações',
            'items' => $reviews->listForTenant($tid),
            'stats' => $reviews->statsForTenant($tid),
            'currentNav' => 'reviews',
        ]);
    }

    public function toggleVisibility(): Response
    {
        $tid = $this->tenantId();
        $reviewId = (int) $this->request->input('review_id');
        $isPublic = (int) $this->request->input('is_public') === 1;
        $ok = (new ReviewModel())->setPublic($tid, $reviewId, $isPublic);
        Flash::set($ok ? 'success' : 'error', $ok
            ? ($isPublic ? 'Avaliação visível publicamente.' : 'Avaliação ocultada.')
            : 'Não foi possível atualizar a avaliação.');

        return Response::redirect('/avaliacoes');
    }
}
