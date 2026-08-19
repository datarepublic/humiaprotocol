#!/usr/bin/env python3
import argparse
import json
import re
import sys
import urllib.error
import urllib.parse
import urllib.request

MAX_BODY = 128 * 1024
TIMEOUT = 8

def origin_of(value: str) -> str:
    p = urllib.parse.urlsplit(value.strip())
    if p.scheme != "https" or not p.hostname:
        raise ValueError("origin must be an https:// URL")
    if p.port not in (None, 443):
        raise ValueError("only HTTPS port 443 is supported")
    return f"https://{p.hostname.lower()}" + (f":{p.port}" if p.port else "")

def fetch(url: str) -> tuple[int, str, str]:
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "HUMIA-v0.4-interop-check/0.1"},
    )
    with urllib.request.urlopen(req, timeout=TIMEOUT) as r:
        body = r.read(MAX_BODY + 1)
        if len(body) > MAX_BODY:
            raise ValueError(f"body too large: {url}")
        return r.status, r.headers.get("Content-Type", ""), body.decode("utf-8", "replace")

def content_usage(robots: str) -> list[str]:
    values = []
    for line in robots.splitlines():
        m = re.match(r"^\s*Content-Usage\s*:\s*(.+?)\s*$", line, re.I)
        if m:
            values.append(m.group(1))
    return values

def humia_discovery(robots: str) -> list[str]:
    values = []
    for line in robots.splitlines():
        m = re.match(r"^\s*Humia\s*:\s*(https://\S+)\s*$", line, re.I)
        if m:
            values.append(m.group(1))
    return values

def main() -> int:
    ap = argparse.ArgumentParser(description="Experimental HUMIA + AIPREF live interoperability checker")
    ap.add_argument("origin", help="HTTPS origin, e.g. https://hangarrc.com")
    args = ap.parse_args()

    try:
        origin = origin_of(args.origin)
        robots_url = origin + "/robots.txt"
        humia_url = origin + "/.well-known/humia.json"

        robots_status, _, robots = fetch(robots_url)
        humia_status, humia_ct, humia_body = fetch(humia_url)
        manifest = json.loads(humia_body)

        checks = []

        def check(name, ok, detail):
            checks.append((name, bool(ok), detail))

        check("robots HTTP", robots_status == 200, f"HTTP {robots_status}")
        check("HUMIA HTTP", humia_status == 200, f"HTTP {humia_status}")
        check("HUMIA media type", "json" in humia_ct.lower(), humia_ct or "(none)")
        check("protocol", manifest.get("protocol") == "HUMIA", repr(manifest.get("protocol")))
        check("version", manifest.get("version") in {"0.3", "0.4"}, repr(manifest.get("version")))

        canonical = manifest.get("identity", {}).get("canonical")
        canonical_origin = None
        try:
            canonical_origin = origin_of(canonical) if isinstance(canonical, str) else None
        except ValueError:
            pass
        check("canonical origin", canonical_origin == origin, repr(canonical))

        aipref = content_usage(robots)
        check("AIPREF Content-Usage", bool(aipref), ", ".join(aipref) if aipref else "missing")

        humia_lines = humia_discovery(robots)
        check("HUMIA robots discovery", humia_url in humia_lines, ", ".join(humia_lines) if humia_lines else "missing")

        print(f"HUMIA v0.4 interoperability experiment — {origin}")
        print()
        for name, ok, detail in checks:
            print(f"{'PASS' if ok else 'FAIL'}  {name}: {detail}")

        ok = all(x[1] for x in checks)
        print()
        if ok:
            print("RESULT: PASS — HUMIA and AIPREF are simultaneously discoverable on this live origin.")
            return 0

        print("RESULT: FAIL — one or more interoperability checks failed.")
        return 1

    except (ValueError, json.JSONDecodeError, urllib.error.URLError, urllib.error.HTTPError) as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 2

if __name__ == "__main__":
    raise SystemExit(main())
