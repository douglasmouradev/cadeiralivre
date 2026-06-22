#!/usr/bin/env python3
"""Gera logo HQ da Adriele Cardoso a partir da arte em scripts/assets/adriele-logo-source.png."""

from __future__ import annotations

import sys
from pathlib import Path

import numpy as np
from PIL import Image, ImageEnhance, ImageFilter, ImageOps

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_SOURCE = ROOT / "scripts/assets/adriele-logo-source.png"
OUTPUTS = [
    ROOT / "public/assets/img/brands/adriele-cardoso-logo.png",
    ROOT / "public/assets/tenant-logos/adriele-cardoso-nail-design.png",
    ROOT / "storage/uploads/logos/adriele-cardoso.png",
]
CANVAS = 1024
BG = (245, 244, 240)


def trim_content_box(im: Image.Image, threshold: int = 236, pad: int = 20) -> Image.Image:
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


def clean_background(im: Image.Image, threshold: int = 242) -> Image.Image:
    arr = np.array(im.convert("RGB"), dtype=np.uint8)
    near_bg = (
        (arr[:, :, 0] > threshold)
        & (arr[:, :, 1] > threshold)
        & (arr[:, :, 2] > threshold)
    )
    arr[near_bg] = BG
    return Image.fromarray(arr, "RGB")


def enhance_logo(im: Image.Image) -> Image.Image:
    im = ImageEnhance.Contrast(im).enhance(1.04)
    im = ImageEnhance.Sharpness(im).enhance(1.06)
    im = im.filter(ImageFilter.UnsharpMask(radius=1.0, percent=115, threshold=2))
    return im


def fit_square_canvas(logo: Image.Image, size: int = CANVAS) -> Image.Image:
    margin = int(size * 0.08)
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
    im = trim_content_box(im)
    im = clean_background(im)
    im = enhance_logo(im)
    target = int(CANVAS * 0.84)
    upscale = target / max(im.width, im.height)
    if upscale > 1.0:
        im = im.resize(
            (int(im.width * upscale), int(im.height * upscale)),
            Image.Resampling.LANCZOS,
        )
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
