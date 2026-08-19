#!/usr/bin/env python3
from pathlib import Path
import json
import re

ROOT = Path(__file__).resolve().parents[1]
SITE = ROOT / "site"

index = (SITE / "index.html").read_text(encoding="utf-8")
robots = (SITE / "robots.txt").read_text(encoding="utf-8")
policy = json.loads((SITE / ".well-known" / "humia.json").read_text(encoding="utf-8"))
js = (SITE / "js" / "humia.js").read_text(encoding="utf-8")

assert policy["protocol"] == "HUMIA"
assert policy["version"] == "0.3"
assert policy["status"] == "draft"
assert "usage" in policy and "training" in policy["usage"]
assert "Humia: https://humiaprotocol.org/.well-known/humia.json" in robots
assert 'id="generator"' in index
assert 'data-generator-form' in index
assert 'data-policy-summary' in index
assert 'What this policy means' in index
assert 'data-verify-robots-url' in index
assert 'No account. No email. No upload.' in index
assert 'spec/v0.3/' in index
assert (SITE / "spec" / "v0.3" / "index.html").exists()
assert "fetch(" not in js and "XMLHttpRequest" not in js
assert "not_requested" not in js
assert '"principle": "measurable_reciprocal_access"' not in index
assert '"usage_reporting": "requested"' in index
assert "form action=" not in index.lower()
assert not re.search(r'<script[^>]+src=["\']https?://', index, re.I)
assert not re.search(r'<link[^>]+rel=["\'](?:stylesheet|preload|icon)["\'][^>]+href=["\']https?://', index, re.I)

print("HUMIA static contract: OK")
