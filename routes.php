<?php

declare(strict_types=1);

use App\Core\Router;

return static function (Router $r): void {
    $r->add('GET', '/', 'HomeController@index', []);

    $r->add('GET', '/login', 'AuthController@showLogin', []);
    $r->add('POST', '/login', 'AuthController@login', ['LoginRateLimitMiddleware', 'CsrfMiddleware']);
    $r->add('GET', '/registrar', 'AuthController@showRegister', []);
    $r->add('POST', '/registrar', 'AuthController@register', ['CsrfMiddleware']);
    $r->add('GET', '/logout', 'AuthController@logout', []);
    $r->add('GET', '/esqueci-senha', 'AuthController@showForgot', []);
    $r->add('POST', '/esqueci-senha', 'AuthController@forgot', ['CsrfMiddleware']);
    $r->add('GET', '/redefinir-senha', 'AuthController@showReset', []);
    $r->add('POST', '/redefinir-senha', 'AuthController@reset', ['CsrfMiddleware']);

    $r->add('GET', '/painel', 'DashboardController@index', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('GET', '/painel/api/hoje', 'DashboardController@todayJson', ['AuthMiddleware', 'AdminMiddleware']);

    $r->add('GET', '/servicos', 'ServiceController@index', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('GET', '/servicos/novo', 'ServiceController@createForm', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('POST', '/servicos', 'ServiceController@create', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('GET', '/servicos/{id}/editar', 'ServiceController@editForm', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('POST', '/servicos/{id}', 'ServiceController@update', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/servicos/{id}/excluir', 'ServiceController@delete', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/servicos/ordem', 'ServiceController@reorder', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);

    $r->add('GET', '/barbeiros', 'BarberController@index', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('GET', '/barbeiros/novo', 'BarberController@createForm', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('POST', '/barbeiros', 'BarberController@create', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('GET', '/barbeiros/{id}/editar', 'BarberController@editForm', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('POST', '/barbeiros/{id}', 'BarberController@update', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/barbeiros/{id}/horarios', 'BarberController@saveHours', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/barbeiros/{id}/bloqueios', 'BarberController@addBlock', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/barbeiros/{id}/bloqueios/{blockId}/excluir', 'BarberController@deleteBlock', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/barbeiros/{id}/disponibilidade', 'BarberController@toggle', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/barbeiros/{id}/desativar', 'BarberController@deactivate', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);

    $r->add('GET', '/agenda', 'ScheduleController@index', ['AuthMiddleware', 'StaffMiddleware']);
    $r->add('POST', '/agenda', 'ScheduleController@store', ['AuthMiddleware', 'StaffMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/agenda/status', 'ScheduleController@updateStatus', ['AuthMiddleware', 'StaffMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/agenda/reagendar', 'ScheduleController@reschedule', ['AuthMiddleware', 'StaffMiddleware', 'CsrfMiddleware']);
    $r->add('GET', '/agenda/slots.json', 'ScheduleController@slotsJson', ['AuthMiddleware', 'StaffMiddleware']);

    $r->add('GET', '/clientes', 'ClientController@index', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('GET', '/clientes/exportar', 'ClientController@export', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('GET', '/clientes/{id}', 'ClientController@show', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('GET', '/clientes/{id}/editar', 'ClientController@editForm', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('POST', '/clientes/{id}', 'ClientController@update', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);

    $r->add('GET', '/relatorios', 'ReportController@index', ['AuthMiddleware', 'AdminMiddleware']);

    $r->add('GET', '/configuracoes', 'SettingsController@index', ['AuthMiddleware', 'AdminMiddleware']);
    $r->add('POST', '/configuracoes/tenant', 'SettingsController@updateTenant', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/configuracoes/logo', 'SettingsController@uploadLogo', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/configuracoes/perfil', 'SettingsController@updateProfile', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/configuracoes/avatar', 'SettingsController@uploadAvatar', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);
    $r->add('POST', '/configuracoes/equipe', 'SettingsController@storeStaffUser', ['AuthMiddleware', 'AdminMiddleware', 'CsrfMiddleware']);

    $r->add('GET', '/media/logo/{slug}', 'MediaController@tenantLogo', []);

    $r->add('GET', '/cliente/{slug}/entrar', 'ClientPortalController@showLogin', []);
    $r->add('POST', '/cliente/{slug}/entrar', 'ClientPortalController@login', ['LoginRateLimitMiddleware', 'CsrfMiddleware']);
    $r->add('GET', '/cliente/{slug}/cadastro', 'ClientPortalController@showRegister', []);
    $r->add('POST', '/cliente/{slug}/cadastro', 'ClientPortalController@register', ['CsrfMiddleware']);
    $r->add('GET', '/cliente/{slug}/sair', 'ClientPortalController@logout', []);

    $r->add('GET', '/agendar/{slug}', 'PublicBookingController@index', []);
    $r->add('GET', '/agendar/{slug}/slots.json', 'PublicBookingController@slots', []);
    $r->add('POST', '/agendar/{slug}', 'PublicBookingController@book', ['CsrfMiddleware']);
    $r->add('GET', '/agendar/{slug}/obrigado', 'PublicBookingController@thanks', []);
    $r->add('GET', '/agendar/{slug}/confirmar', 'PublicBookingController@confirmForm', []);
    $r->add('POST', '/agendar/{slug}/confirmar', 'PublicBookingController@confirm', ['CsrfMiddleware']);
    $r->add('GET', '/agendar/cancelar', 'PublicBookingController@cancelForm', []);
    $r->add('POST', '/agendar/cancelar', 'PublicBookingController@cancel', ['CsrfMiddleware']);
    $r->add('GET', '/avaliar', 'PublicBookingController@reviewForm', []);
    $r->add('POST', '/avaliar', 'PublicBookingController@review', ['CsrfMiddleware']);
};
