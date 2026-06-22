#!/usr/bin/env python3
"""Gera favicons a partir de public/assets/img/cadeiralivre-logo.png."""

from __future__ import annotations

from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parents[1]
SRC = ROOT / 'public/assets/img/cadeiralivre-logo.png'
OUT = ROOT / 'public'

SIZES: dict[str, int] = {
    'favicon-16x16.png': 16,
    'favicon-32x32.png': 32,
    'apple-touch-icon.png': 180,
    'android-chrome-192x192.png': 192,
    'android-chrome-512x512.png': 512,
}


def main() -> None:
    if not SRC.is_file():
        raise SystemExit(f'Logo não encontrado: {SRC}')

    img = Image.open(SRC).convert('RGBA')
    icons: list[Image.Image] = []

    for name, size in SIZES.items():
        resized = img.resize((size, size), Image.Resampling.LANCZOS)
        resized.save(OUT / name, optimize=True)
        if size in (16, 32, 48):
            icons.append(resized)

    if not icons:
        icons = [img.resize((32, 32), Image.Resampling.LANCZOS)]

    ico_sizes = [(16, 16), (32, 32), (48, 48)]
    ico_images = [img.resize(s, Image.Resampling.LANCZOS) for s in ico_sizes]
    ico_images[0].save(
        OUT / 'favicon.ico',
        format='ICO',
        sizes=ico_sizes,
        append_images=ico_images[1:],
    )

    print('Favicons gerados em public/')


if __name__ == '__main__':
    main()
