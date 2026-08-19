#!/usr/bin/env python3
import argparse
import json
import shutil
import sys
from pathlib import Path
from urllib.parse import urlsplit

def normalize_origin(value: str) -> str:
    value = value.strip()
    p = urlsplit(value)
    if p.scheme != "https" or not p.hostname:
        raise ValueError("origin must be an HTTPS origin, for example https://example.com")
    if p.username or p.password:
        raise ValueError("origin must not contain credentials")
    if p.port not in (None, 443):
        raise ValueError("pilot supports HTTPS port 443 only")
    if p.path not in ("", "/") or p.query or p.fragment:
        raise ValueError("origin must not include a path, query, or fragment")
    return f"https://{p.hostname.lower()}/"

def token(value: str) -> str:
    return "y" if value == "allow" else "n"

def main() -> int:
    ap = argparse.ArgumentParser(
        description="Generate HUMIA v0.3 + experimental AIPREF pilot files"
    )
    ap.add_argument("--origin", required=True, help="HTTPS site origin")
    ap.add_argument("--name", required=True, help="Human-readable site name")
    ap.add_argument("--training", required=True, choices=("allow", "deny"))
    ap.add_argument("--search", required=True, choices=("allow", "deny"))
    ap.add_argument("--output", default="generated", help="Output directory")
    ap.add_argument("--force", action="store_true", help="Replace an existing output directory")
    args = ap.parse_args()

    try:
        origin = normalize_origin(args.origin)
    except ValueError as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 2

    name = args.name.strip()
    if not name:
        print("ERROR: site name must not be empty", file=sys.stderr)
        return 2

    out = Path(args.output)
    if out.exists():
        if not args.force:
            print(f"ERROR: output already exists: {out} (use --force to replace)", file=sys.stderr)
            return 2
        if out.is_dir():
            shutil.rmtree(out)
        else:
            out.unlink()

    (out / ".well-known").mkdir(parents=True)

    manifest = {
        "protocol": "HUMIA",
        "version": "0.3",
        "status": "draft",
        "identity": {
            "name": name,
            "canonical": origin
        },
        "access": {
            "public_content": "allow",
            "private_api": "deny"
        },
        "usage": {
            "user_assistance": "allow",
            "search_retrieval": args.search,
            "bulk_crawl": "deny",
            "training": args.training
        },
        "attribution": {
            "required": True,
            "canonical_url": True
        },
        "reciprocity": {
            "usage_reporting": "requested"
        }
    }

    manifest_path = out / ".well-known" / "humia.json"
    manifest_path.write_text(json.dumps(manifest, indent=2) + "\n", encoding="utf-8")

    additions = f"""# HUMIA + AIPREF pilot additions
# Review and MERGE these lines into the site's existing robots.txt.
# For this pilot, place Content-Usage in the intended robots group
# (commonly the existing User-agent: * group for a site-wide preference).
Content-Usage: train-ai={token(args.training)}, search={token(args.search)}

# HUMIA Protocol discovery (experimental)
Humia: {origin}.well-known/humia.json
"""
    additions_path = out / "robots-additions.txt"
    additions_path.write_text(additions, encoding="utf-8")

    print("Generated HUMIA + AIPREF pilot files")
    print(f"origin:   {origin}")
    print(f"name:     {name}")
    print(f"training: {args.training} -> train-ai={token(args.training)}")
    print(f"search:   {args.search} -> search={token(args.search)}")
    print()
    print(manifest_path)
    print(additions_path)
    print()
    print("IMPORTANT: merge robots-additions.txt into the existing robots.txt; do not overwrite it.")
    return 0

if __name__ == "__main__":
    raise SystemExit(main())
