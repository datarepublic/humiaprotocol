# HUMIA Protocol

> **The Web has a language for crawling. It needs a language for cooperation.**

HUMIA is an experimental open, website-first protocol for cooperation between human-controlled websites and AI agents.

## IETF Internet-Draft

HUMIA Protocol is published as an individual IETF Internet-Draft:

- **Title:** *HUMIA: A Website-First Protocol for Human-AI Cooperation*
- **Draft:** `draft-treneule-humia-protocol-00`
- **Datatracker:** https://datatracker.ietf.org/doc/draft-treneule-humia-protocol/
- **HTML:** https://www.ietf.org/archive/id/draft-treneule-humia-protocol-00.html

Publication as an Internet-Draft does **not** imply IETF endorsement or standards status.


## Interoperability

HUMIA Protocol is designed to complement, not replace, neighboring Web and AI mechanisms. The current scope and planned boundaries with REP, IETF AIPREF and W3C A2WF are documented in [`docs/INTEROPERABILITY.md`](docs/INTEROPERABILITY.md).

## Public implementation

- **Website:** https://humiaprotocol.org/
- **Canonical policy:** https://humiaprotocol.org/.well-known/humia.json
- **Current public candidate:** **HUMIA Protocol v0.3 Draft**

## V0 website

The public site is deliberately static and dependency-free:

- `site/index.html`
- `site/css/humia.css`
- `site/js/humia.js`
- `site/assets/`
- `site/.well-known/humia.json`
- `site/spec/v0.3/`

## Generator V0

The generator runs entirely in the user's browser. It requires no account, backend, email, analytics or form submission. It creates:

1. a `robots.txt` HUMIA discovery snippet;
2. a downloadable `humia.json` draft.

Default preset: **Balanced**.

## Status

Experimental. HUMIA is not an IETF standard, is not currently registered in the IANA Well-Known URIs registry, and no AI provider is claimed to support it.

## Beginner-first installation

The public site now includes a plain-language installation guide for non-technical webmasters:

1. add the generated HUMIA discovery lines to the existing `/robots.txt`;
2. create `/.well-known/` at the public web root and upload `humia.json`;
3. open both public URLs in a browser to verify publication.

The homepage also carries the project line: **“The Web has a language for crawling. It needs a language for cooperation.”**


## Plain-language policy explanation

Generator V0 also translates the generated machine policy into a short human-readable summary. It explains the selected permissions for user assistance, search/retrieval, bulk crawling, training, attribution and usage reporting, and shows both public URLs to verify after installation.
