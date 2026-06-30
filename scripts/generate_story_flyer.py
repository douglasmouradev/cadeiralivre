#!/usr/bin/env python3
"""Gera flyer vertical 9:16 (Stories) para o CadeiraLivre."""

from __future__ import annotations

from pathlib import Path

from PIL import Image, ImageDraw, ImageFont, ImageFilter

ROOT = Path(__file__).resolve().parents[1]
LOGO = ROOT / 'public/assets/img/cadeiralivre-logo.png'
OUT = ROOT / 'public/assets/img/cadeiralivre-flyer-stories.png'

W, H = 1080, 1920

BG = (247, 244, 239)
BRASS = (124, 94, 60)
BRASS_DARK = (92, 69, 46)
TEXT = (28, 25, 23)
MUTED = (107, 101, 96)
WHITE = (255, 255, 255)


def load_font(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        'C:/Windows/Fonts/georgiab.ttf' if bold else 'C:/Windows/Fonts/georgia.ttf',
        'C:/Windows/Fonts/timesbd.ttf' if bold else 'C:/Windows/Fonts/times.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSerif-Bold.ttf' if bold else '/usr/share/fonts/truetype/dejavu/DejaVuSerif.ttf',
    ]
    for path in candidates:
        if Path(path).is_file():
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def load_sans(size: int, bold: bool = False) -> ImageFont.FreeTypeFont | ImageFont.ImageFont:
    candidates = [
        'C:/Windows/Fonts/segoeuib.ttf' if bold else 'C:/Windows/Fonts/segoeui.ttf',
        'C:/Windows/Fonts/arialbd.ttf' if bold else 'C:/Windows/Fonts/arial.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf' if bold else '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    ]
    for path in candidates:
        if Path(path).is_file():
            return ImageFont.truetype(path, size)
    return ImageFont.load_default()


def draw_radial_glow(base: Image.Image, cx: int, cy: int, radius: int, color: tuple[int, int, int, int]) -> None:
    glow = Image.new('RGBA', (W, H), (0, 0, 0, 0))
    gdraw = ImageDraw.Draw(glow)
    for r in range(radius, 0, -8):
        alpha = int(color[3] * (r / radius) ** 2)
        gdraw.ellipse((cx - r, cy - r, cx + r, cy + r), fill=(*color[:3], alpha))
    glow = glow.filter(ImageFilter.GaussianBlur(40))
    base.alpha_composite(glow)


def wrap_text(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont, max_width: int) -> list[str]:
    words = text.split()
    lines: list[str] = []
    current = ''
    for word in words:
        trial = f'{current} {word}'.strip()
        if draw.textlength(trial, font=font) <= max_width:
            current = trial
        else:
            if current:
                lines.append(current)
            current = word
    if current:
        lines.append(current)
    return lines


def draw_phone_mockup(base: Image.Image, x: int, y: int) -> None:
    pw, ph = 420, 780
    phone = Image.new('RGBA', (pw, ph), (0, 0, 0, 0))
    pd = ImageDraw.Draw(phone)

    pd.rounded_rectangle((0, 0, pw - 1, ph - 1), radius=48, fill=(255, 255, 255, 255), outline=(210, 195, 175, 255), width=4)
    pd.rounded_rectangle((24, 80, pw - 24, ph - 24), radius=32, fill=(252, 250, 246, 255))

    sans = load_sans(22, bold=True)
    pd.text((pw // 2, 120), 'Escolha o horário', fill=TEXT, font=sans, anchor='mm')

    # Calendar row
    days = [('SEG', '19'), ('TER', '20'), ('QUA', '21'), ('QUI', '22'), ('SEX', '23')]
    start_x = 36
    for i, (label, num) in enumerate(days):
        cx = start_x + i * 72
        active = num == '21'
        if active:
            pd.ellipse((cx, 160, cx + 56, 216), fill=BRASS)
            pd.text((cx + 28, 172), label, fill=WHITE, font=load_sans(14), anchor='mm')
            pd.text((cx + 28, 198), num, fill=WHITE, font=load_sans(22, bold=True), anchor='mm')
        else:
            pd.text((cx + 28, 172), label, fill=MUTED, font=load_sans(14), anchor='mm')
            pd.text((cx + 28, 198), num, fill=TEXT, font=load_sans(22, bold=True), anchor='mm')

    # Time slots grid
    slots = ['09:00', '09:30', '10:00', '10:30', '11:00', '14:00', '14:30', '15:00', '15:30', '16:00']
    sx, sy = 36, 250
    for i, slot in enumerate(slots):
        col, row = i % 2, i // 2
        bx = sx + col * 170
        by = sy + row * 72
        active = slot == '14:00'
        fill = BRASS if active else (243, 237, 228, 255)
        fg = WHITE if active else TEXT
        pd.rounded_rectangle((bx, by, bx + 150, by + 56), radius=14, fill=fill)
        pd.text((bx + 75, by + 28), slot, fill=fg, font=load_sans(20, bold=active), anchor='mm')

    pd.rounded_rectangle((36, ph - 120, pw - 36, ph - 48), radius=20, fill=BRASS)
    pd.text((pw // 2, ph - 84), 'AGENDAR', fill=WHITE, font=load_sans(24, bold=True), anchor='mm')

    shadow = Image.new('RGBA', (pw + 40, ph + 40), (0, 0, 0, 0))
    ImageDraw.Draw(shadow).rounded_rectangle((20, 20, pw + 20, ph + 20), radius=48, fill=(28, 25, 23, 60))
    shadow = shadow.filter(ImageFilter.GaussianBlur(18))
    base.alpha_composite(shadow, (x - 20, y - 10))
    base.alpha_composite(phone, (x, y))


def main() -> None:
    if not LOGO.is_file():
        raise SystemExit(f'Logo não encontrado: {LOGO}')

    img = Image.new('RGBA', (W, H), (*BG, 255))
    draw_radial_glow(img, W // 2, 320, 520, (232, 220, 200, 90))
    draw_radial_glow(img, W // 2, H - 200, 400, (124, 94, 60, 35))

    draw = ImageDraw.Draw(img)

    # Logo
    logo = Image.open(LOGO).convert('RGBA')
    logo_size = 200
    logo = logo.resize((logo_size, logo_size), Image.Resampling.LANCZOS)
    img.alpha_composite(logo, ((W - logo_size) // 2, 72))

    # Headline
    serif_lg = load_font(72)
    serif_md = load_font(64)
    y = 300
    for line, font in [('Sua cadeira.', serif_lg), ('Seu horário.', serif_lg), ('Online.', serif_md)]:
        tw = draw.textlength(line, font=font)
        draw.text(((W - tw) / 2, y), line, fill=TEXT, font=font)
        y += 82

    sub = 'Agendamento online para barbearias, nails e salões'
    sans = load_sans(28)
    for i, line in enumerate(wrap_text(draw, sub, sans, 900)):
        tw = draw.textlength(line, font=sans)
        draw.text(((W - tw) / 2, y + 24 + i * 38), line, fill=MUTED, font=sans)

    draw_phone_mockup(img, (W - 420) // 2, 620)

    # Features
    features = [
        'Link público de agendamento',
        'Portal do cliente + lembretes WhatsApp',
        'Painel completo para sua equipe',
    ]
    fy = 1440
    sans_sm = load_sans(26)
    for feat in features:
        draw.ellipse((120, fy + 8, 140, fy + 28), fill=BRASS)
        draw.text((160, fy), feat, fill=TEXT, font=sans_sm)
        fy += 52

    # CTA button
    btn_w, btn_h = 680, 96
    btn_x = (W - btn_w) // 2
    btn_y = 1620
    draw.rounded_rectangle((btn_x, btn_y, btn_x + btn_w, btn_y + btn_h), radius=48, fill=BRASS_DARK)
    cta = '14 DIAS GRÁTIS'
    cta_font = load_sans(36, bold=True)
    draw.text((W // 2, btn_y + btn_h // 2), cta, fill=WHITE, font=cta_font, anchor='mm')

    url = 'cadeiralivre.tdesksolutions.com.br'
    url_font = load_sans(24)
    draw.text((W // 2, 1750), url, fill=MUTED, font=url_font, anchor='mm')

    hint_font = load_sans(20)
    draw.text((W // 2, 1810), 'Saiba mais  ↑', fill=BRASS, font=hint_font, anchor='mm')

    OUT.parent.mkdir(parents=True, exist_ok=True)
    img.convert('RGB').save(OUT, 'PNG', optimize=True)
    print(f'Gerado: {OUT} ({W}x{H})')


if __name__ == '__main__':
    main()
