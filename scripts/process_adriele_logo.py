#!/usr/bin/env python3
"""Gera logo HQ da Adriele Cardoso a partir da foto original."""

from __future__ import annotations

import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageEnhance, ImageFilter, ImageOps

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_SOURCE = (
    ROOT / "scripts/assets/adriele-logo-source.png"
)
OUTPUTS = [
    ROOT / "public/assets/img/brands/adriele-cardoso-logo.png",
    ROOT / "public/assets/tenant-logos/adriele-cardoso-nail-design.png",
    ROOT / "storage/uploads/logos/adriele-cardoso.png",
]
CANVAS = 1024
BG = (250, 248, 245)


def trim_content_box(im: Image.Image, threshold: int = 241, pad: int = 18) -> Image.Image:
    gray = np.array(im.convert("L"))
    mask = gray < threshold
    ys, xs = np.where(mask)
    if len(xs) == 0:
        return im
    left = max(0, int(xs.min()) - pad)
    top = max(0, int(ys.min()) - pad)
    right = min(im.width, int(xs.max()) + pad + 1)
    bottom = min(im.height, int(ys.max()) + pad + 1)
    return im.crop((left, top, right, bottom))


def clean_background(im: Image.Image, threshold: int = 238) -> Image.Image:
    arr = np.array(im.convert("RGB"), dtype=np.uint8)
    near_white = (
        (arr[:, :, 0] > threshold)
        & (arr[:, :, 1] > threshold)
        & (arr[:, :, 2] > threshold)
    )
    arr[near_white] = BG
    return Image.fromarray(arr, "RGB")


def enhance_logo(im: Image.Image) -> Image.Image:
    im = ImageEnhance.Contrast(im).enhance(1.07)
    im = ImageEnhance.Color(im).enhance(1.12)
    im = im.filter(ImageFilter.UnsharpMask(radius=1.4, percent=145, threshold=2))
    im = ImageEnhance.Sharpness(im).enhance(1.08)
    return im


def fit_square_canvas(logo: Image.Image, size: int = CANVAS) -> Image.Image:
    margin = int(size * 0.09)
    max_side = size - margin * 2
    ratio = min(max_side / logo.width, max_side / logo.height)
    new_w = max(1, int(logo.width * ratio))
    new_h = max(1, int(logo.height * ratio))
    logo = logo.resize((new_w, new_h), Image.Resampling.LANCZOS)
    canvas = Image.new("RGB", (size, size), BG)
    ox = (size - new_w) // 2
    oy = (size - new_h) // 2
    canvas.paste(logo, (ox, oy))
    return canvas


def process(source: Path) -> Image.Image:
    im = Image.open(source)
    im = ImageOps.exif_transpose(im).convert("RGB")
    w, h = im.size
    # Remove reflexo superior, dedos inferiores e bordas da foto.
    im = im.crop((int(w * 0.09), int(h * 0.13), int(w * 0.91), int(h * 0.71)))
    im = trim_content_box(im)
    im = clean_background(im)
    im = enhance_logo(im)
  # Upscale intermediário para nitidez em telas retina.
    upscale = 1024 / max(im.width, im.height) * 1.35
    if upscale > 1.0:
        im = im.resize(
            (int(im.width * upscale), int(im.height * upscale)),
            Image.Resampling.LANCZOS,
        )
        im = im.filter(ImageFilter.UnsharpMask(radius=1.0, percent=120, threshold=2))
    return fit_square_canvas(im)


def main() -> int:
    source = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_SOURCE
    if not source.is_file():
        print(f"Fonte não encontrada: {source}", file=sys.stderr)
        return 1

    logo = process(source)
    for out in OUTPUTS:
        out.parent.mkdir(parents=True, exist_ok=True)
        logo.save(out, "PNG", optimize=True, compress_level=6)
        print(f"OK {out} ({logo.width}x{logo.height})")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
