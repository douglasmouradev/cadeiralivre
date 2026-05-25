#!/usr/bin/env python3
"""Remove fundo xadrez (cinza/branco) da logo e deixa transparente ou cor sólida."""
from __future__ import annotations

import sys
from pathlib import Path

from PIL import Image

ROOT = Path(__file__).resolve().parent.parent
DEFAULT = ROOT / "public" / "assets" / "img" / "cadeiralivre-logo.png"

# Tom creme do app (--bg) quando --solid
SOLID_BG = (242, 239, 232, 255)


def is_background_pixel(r: int, g: int, b: int, a: int) -> bool:
    if a < 128:
        return True
    spread = max(r, g, b) - min(r, g, b)
    avg = (r + g + b) / 3
    # Quadrados do editor: cinza claro ou branco neutro
    if spread <= 28 and avg >= 192:
        return True
    return False


def fix(path: Path, *, transparent: bool = True) -> None:
    im = Image.open(path).convert("RGBA")
    px = im.load()
    w, h = im.size
    for y in range(h):
        for x in range(w):
            r, g, b, a = px[x, y]
            if not is_background_pixel(r, g, b, a):
                continue
            if transparent:
                px[x, y] = (r, g, b, 0)
            else:
                px[x, y] = SOLID_BG
    im.save(path, format="PNG", optimize=True)
    mode = "transparente" if transparent else f"sólido {SOLID_BG[:3]}"
    print(f"OK: {path} ({w}×{h}) — fundo {mode}")


def main() -> None:
    target = Path(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT
    solid = "--solid" in sys.argv
    if not target.is_file():
        raise SystemExit(f"Arquivo não encontrado: {target}")
    fix(target, transparent=not solid)


if __name__ == "__main__":
    main()
