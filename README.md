# HUMIA Protocol

HUMIA is an experimental open protocol for cooperation between human-controlled websites and AI agents.

This repository is intentionally starting small. The current public website is a static V0 designed for `humiaprotocol.org`. It introduces the protocol without claiming adoption by AI operators.

## Current structure

- `site/` — static public website (HTML/CSS/vanilla JS)
- `examples/` — illustrative HUMIA examples
- `spec/v0.1/` — reserved for the audited v0.1 specification
- `schemas/` — reserved for audited schemas
- `docs/` — reserved for protocol documentation
- `tests/` — reserved for validation and regression tests

## Local preview

```bash
cd site
python3 -m http.server 8080
```

Then open `http://localhost:8080`.

## Deployment target

The V0 is designed to be uploaded as static files to the document root of the HUMIA Protocol hosting account.

## Status

HUMIA Protocol v0.1 is a draft. The website, examples and terminology may evolve as the specification is audited and extracted from earlier prototype work.
