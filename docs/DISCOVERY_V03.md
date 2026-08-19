# HUMIA discovery — draft v0.3

HUMIA draft v0.3 proposes two complementary discovery paths.

## 1. Canonical discovery

A publisher exposes its policy at:

```text
https://example.org/.well-known/humia.json
```

This is the canonical HUMIA policy location. The current `humia.json` suffix is experimental and is not presented as an IANA-registered well-known URI.

## 2. robots.txt discovery bridge

A publisher may additionally advertise the canonical policy in `/robots.txt`:

```text
User-agent: *
Allow: /

Humia: https://example.org/.well-known/humia.json

Sitemap: https://example.org/sitemap.xml
```

`Humia:` is an experimental HUMIA extension record, not a Robots Exclusion Protocol rule. Existing crawlers that do not implement HUMIA are expected to ignore or otherwise not act on this record; HUMIA-aware agents can use it as an explicit discovery hint.

The presence or absence of `Humia:` MUST NOT alter the meaning of `Allow` or `Disallow` rules.

## Discovery behavior for HUMIA-aware agents

Recommended draft behavior:

1. Attempt the canonical `/.well-known/humia.json` location.
2. When `/robots.txt` is fetched, recognize `Humia:` as an optional discovery hint.
3. If the hint is present, require an HTTPS URL and treat the fetched policy as untrusted input.
4. Apply Robots Exclusion Protocol rules independently from HUMIA policy interpretation.
5. Do not infer HUMIA support merely from traffic or from a crawler visiting the website.

## Status

This mechanism is experimental and subject to change before a stable HUMIA specification.
