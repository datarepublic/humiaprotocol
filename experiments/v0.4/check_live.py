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
    return f"https://{p.hostname.lower()}"

def fetch(url: str):
    req = urllib.request.Request(
        url,
        headers={"User-Agent": "HUMIA-v0.4-interop-check/0.2"},
    )
    with urllib.request.urlopen(req, timeout=TIMEOUT) as r:
        body = r.read(MAX_BODY + 1)
        if len(body) > MAX_BODY:
            raise ValueError(f"body too large: {url}")
        return r.status, r.headers.get("Content-Type", ""), body.decode("utf-8", "replace")

def content_usage_lines(robots: str):
    values = []
    for line in robots.splitlines():
        line = line.split("#", 1)[0]
        m = re.match(r"^\s*Content-Usage\s*:\s*(.+?)\s*$", line, re.I)
        if m:
            values.append(m.group(1))
    return values

def humia_discovery(robots: str):
    values = []
    for line in robots.splitlines():
        line = line.split("#", 1)[0]
        m = re.match(r"^\s*Humia\s*:\s*(https://\S+)\s*$", line, re.I)
        if m:
            values.append(m.group(1))
    return values

def parse_simple_aipref(value: str):
    """
    Experimental subset parser for the simple AIPREF serialization currently
    used by this experiment: lower-case dictionary keys with bare y/n tokens.
    Last duplicate key wins.
    """
    prefs = {}
    malformed = []
    for part in value.split(","):
        part = part.strip()
        m = re.fullmatch(r"([a-z0-9_.*-]+)\s*=\s*([A-Za-z0-9_.*-]+)", part)
        if not m:
            malformed.append(part)
            continue
        key, token = m.group(1), m.group(2)
        prefs[key] = token if token in {"y", "n"} else None
    return prefs, malformed

def human_pref(token):
    return {"y": "ALLOW", "n": "DISALLOW", None: "UNKNOWN"}.get(token, "UNKNOWN")

def humia03_expected(manifest):
    usage = manifest.get("usage")
    if not isinstance(usage, dict):
        return {}

    expected = {}
    if usage.get("training") in {"allow", "deny"}:
        expected["train-ai"] = "y" if usage["training"] == "allow" else "n"
    if usage.get("search_retrieval") in {"allow", "deny"}:
        expected["search"] = "y" if usage["search_retrieval"] == "allow" else "n"
    return expected

def main():
    ap = argparse.ArgumentParser(
        description="Experimental HUMIA + AIPREF live semantic checker"
    )
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

        lines = content_usage_lines(robots)
        check("AIPREF Content-Usage", bool(lines), ", ".join(lines) if lines else "missing")

        parsed = {}
        malformed = []
        for value in lines:
            prefs, bad = parse_simple_aipref(value)
            parsed.update(prefs)
            malformed.extend(bad)

        check(
            "AIPREF simple syntax",
            bool(lines) and not malformed,
            "ok" if lines and not malformed else ("malformed: " + ", ".join(malformed) if malformed else "missing")
        )

        if lines:
            for category in ("train-ai", "search"):
                token = parsed.get(category)
                print_pref = human_pref(token)
                check(
                    f"AIPREF {category}",
                    token in {"y", "n"},
                    f"{token!r} -> {print_pref}"
                )

        humia_lines = humia_discovery(robots)
        check(
            "HUMIA robots discovery",
            humia_url in humia_lines,
            ", ".join(humia_lines) if humia_lines else "missing",
        )

        expected = humia03_expected(manifest)
        for category, expected_token in expected.items():
            actual = parsed.get(category)
            check(
                f"v0.3 migration consistency {category}",
                actual == expected_token,
                f"HUMIA v0.3 expects {expected_token}; AIPREF publishes {actual}"
            )

        print(f"HUMIA v0.4 interoperability experiment — {origin}")
        print()
        for name, ok, detail in checks:
            print(f"{'PASS' if ok else 'FAIL'}  {name}: {detail}")

        print()
        if parsed:
            print("AIPREF semantics:")
            for category in sorted(parsed):
                print(f"  {category}: {human_pref(parsed[category])}")

        print()
        if all(ok for _, ok, _ in checks):
            print("RESULT: PASS — HUMIA and AIPREF are discoverable and semantically consistent for this experiment.")
            return 0

        print("RESULT: FAIL — one or more interoperability checks failed.")
        return 1

    except (ValueError, json.JSONDecodeError, urllib.error.URLError, urllib.error.HTTPError) as exc:
        print(f"ERROR: {exc}", file=sys.stderr)
        return 2

if __name__ == "__main__":
    raise SystemExit(main())
