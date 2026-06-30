<?php

declare(strict_types=1);

namespace App\Helpers;

final class EmailTemplate
{
    /**
     * Layout HTML responsivo para e-mails transacionais.
     */
    public static function layout(string $bodyHtml, ?string $tenantName = null, ?string $accentHex = null): string
    {
        $brand = $tenantName !== null && $tenantName !== '' ? $tenantName : app_name();
        $accent = tenant_brand_hex($accentHex);
        $year = (string) date('Y');
        $app = e(app_name());

        return '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
            . '<title>' . e($brand) . '</title></head>'
            . '<body style="margin:0;padding:0;background:#f7f4ef;font-family:Segoe UI,system-ui,sans-serif;color:#1c1917;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f7f4ef;padding:24px 12px;">'
            . '<tr><td align="center">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border:1px solid rgba(28,25,23,.08);border-radius:16px;overflow:hidden;">'
            . '<tr><td style="background:' . e($accent) . ';height:4px;line-height:4px;font-size:0;">&nbsp;</td></tr>'
            . '<tr><td style="padding:28px 24px 8px;font-family:Georgia,serif;font-size:22px;font-weight:400;">' . e($brand) . '</td></tr>'
            . '<tr><td style="padding:8px 24px 24px;font-size:15px;line-height:1.65;color:#3d3835;">' . $bodyHtml . '</td></tr>'
            . '<tr><td style="padding:16px 24px 24px;border-top:1px solid rgba(28,25,23,.08);font-size:12px;color:#6b6560;">'
            . 'Enviado por ' . $app . ' · ' . $year
            . '</td></tr></table></td></tr></table></body></html>';
    }

    public static function paragraph(string $html): string
    {
        return '<p style="margin:0 0 14px;">' . $html . '</p>';
    }

    public static function button(string $href, string $label, ?string $accentHex = null): string
    {
        $accent = tenant_brand_hex($accentHex);

        return '<p style="margin:20px 0 8px;"><a href="' . e($href) . '" style="display:inline-block;padding:12px 22px;background:'
            . e($accent) . ';color:#ffffff;text-decoration:none;border-radius:999px;font-weight:600;font-size:14px;">'
            . e($label) . '</a></p>';
    }

    public static function mutedLink(string $href, string $label): string
    {
        return '<p style="margin:8px 0 0;font-size:13px;color:#6b6560;"><a href="' . e($href) . '" style="color:#5c452e;">' . e($label) . '</a></p>';
    }
}
