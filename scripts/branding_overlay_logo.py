#!/usr/bin/env python3
"""Sobrepõe o nome CadeiraLivre na faixa principal da logo (substitui BARBER SHOP)."""
from __future__ import annotations

import os
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont

SRC = Path(
    "/Users/douglas/.cursor/projects/Users-douglas-Desktop-barber-shop/assets/image-dcd3fd25-c741-4bab-94a9-9deaee930ee8.png"
)
DST = Path(__file__).resolve().parent.parent / "public" / "assets" / "img" / "cadeiralivre-logo.png"

# Faixa escura central (cobre “BARBER SHOP” por completo)
BOX = (0.06, 0.505, 0.94, 0.675)
FILL = (52, 32, 20)
TEXT_RGB = (228, 210, 180)

FONT_CANDIDATES = [
    "/System/Library/Fonts/Supplemental/BigCaslon.ttf",
    "/System/Library/Fonts/Supplemental/Baskerville.ttc",
    "/System/Library/Fonts/Supplemental/AmericanTypewriter.ttc",
    "/System/Library/Fonts/Supplemental/Times New Roman.ttf",
    "/System/Library/Fonts/Supplemental/Arial.ttf",
]


def load_font(size: int) -> ImageFont.FreeTypeFont:
    for path in FONT_CANDIDATES:
        if not os.path.isfile(path):
            continue
        try:
            if path.endswith(".ttc"):
                return ImageFont.truetype(path, size, index=1)
            return ImageFont.truetype(path, size)
        except OSError:
            continue
    return ImageFont.load_default()


def main() -> None:
    if not SRC.is_file():
        raise SystemExit(f"Arquivo fonte não encontrado: {SRC}")
    DST.parent.mkdir(parents=True, exist_ok=True)
    im = Image.open(SRC).convert("RGBA")
    w, h = im.size
    bx0 = int(w * BOX[0])
    by0 = int(h * BOX[1])
    bx1 = int(w * BOX[2])
    by1 = int(h * BOX[3])
    draw = ImageDraw.Draw(im)
    draw.rectangle([bx0, by0, bx1, by1], fill=FILL)

    text = "CadeiraLivre"
    max_w = bx1 - bx0 - 16
    fontsize = min(52, int((bx1 - bx0) / max(len(text) * 0.55, 4)))
    font = load_font(fontsize)
    tw, th = _measure(draw, text, font)
    while tw > max_w and fontsize > 18:
        fontsize -= 2
        font = load_font(fontsize)
        tw, th = _measure(draw, text, font)

    tx = bx0 + (bx1 - bx0 - tw) // 2
    ty = by0 + (by1 - by0 - th) // 2
    draw.text((tx, ty), text, font=font, fill=TEXT_RGB)
    im.save(DST, format="PNG", optimize=True)
    print(f"Salvo: {DST} ({w}×{h})")


def _measure(draw: ImageDraw.ImageDraw, text: str, font: ImageFont.ImageFont) -> tuple[int, int]:
    bbox = draw.textbbox((0, 0), text, font=font)
    return bbox[2] - bbox[0], bbox[3] - bbox[1]


if __name__ == "__main__":
    main()
