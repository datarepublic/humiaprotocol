# HUMIA ↔ AIPREF Mapping

**Status:** Working draft — non-normative  
**Date:** 2026-08-19  
**HUMIA baseline:** v0.3  
**Goal:** Prepare a practical HUMIA v0.4 without breaking live v0.3 deployments.

## Product objective

HUMIA must be deployable on real websites.

Design rule:
- use RFC 9309 / robots.txt for crawl-path behavior;
- use AIPREF for AI content-use preferences where AIPREF defines them;
- use normal HTTP/auth mechanisms for actual private access control;
- keep HUMIA for the origin-level cooperation profile and useful gaps not covered elsewhere.

## Current AIPREF baseline

Current active vocabulary draft:
- `draft-ietf-aipref-vocab-06`
- core categories include `train-ai` and `search`
- vocabulary is extensible

Attachment draft:
- `draft-ietf-aipref-attach-04`
- describes association through HTTP and robots.txt
- currently expired

Consequence:
HUMIA can align with the AIPREF data model now, but should not hard-code an expired attachment mechanism as if it were final.

## HUMIA v0.3 mapping

| HUMIA v0.3 field | Best home | Decision for v0.4 |
|---|---|---|
| `protocol` | HUMIA | KEEP |
| `version` | HUMIA | KEEP |
| `status` | HUMIA | KEEP |
| `identity.name` | HUMIA | KEEP narrowly |
| `identity.canonical` | HUMIA/Web origin | KEEP |
| `access.public_content` | REP / HTTP | REDESIGN or REMOVE as permission |
| `access.private_api` | HTTP auth | REMOVE from HUMIA core |
| `usage.training` | AIPREF `train-ai` | DELEGATE TO AIPREF |
| `usage.search_retrieval` | AIPREF `search` | MAP CAREFULLY |
| `usage.user_assistance` | no stable AIPREF core equivalent yet | HOLD / EXPERIMENT |
| `usage.bulk_crawl` | REP / crawler policy | REMOVE from downstream AI usage |
| `attribution.required` | partial overlap / provenance | KEEP AS CANDIDATE |
| `attribution.canonical_url` | provenance / canonical source | KEEP AS CANDIDATE |
| `reciprocity.usage_reporting` | HUMIA gap candidate | KEEP / DEVELOP |

## Target shape

HUMIA should move from:

```text
HUMIA = crawl + access + AI usage vocabulary + attribution + reciprocity
```

toward:

```text
HUMIA = origin-level cooperation profile

        ├── REP / robots.txt
        │     crawl behavior
        │
        ├── AIPREF
        │     AI content-use preferences
        │
        ├── HTTP / auth
        │     protected access
        │
        └── HUMIA
              identity
              attribution where useful
              reciprocity
              coordination / discovery
```

## v0.4 implementation principle

Do not replace live v0.3 immediately.

Build v0.4 experimentally in parallel.

First v0.4 prototype should:
1. keep a small `humia.json`;
2. declare which external mechanisms the origin uses;
3. avoid duplicating `train-ai`;
4. avoid treating crawler access as authorization;
5. preserve a small HUMIA-specific cooperation layer;
6. remain easy to generate and validate.

Illustrative direction only:

```json
{
  "protocol": "HUMIA",
  "version": "0.4",
  "status": "draft",
  "identity": {
    "name": "Example",
    "canonical": "https://example.com/"
  },
  "cooperation": {
    "rep": true,
    "aipref": true
  },
  "attribution": {
    "canonical_url": true
  },
  "reciprocity": {
    "usage_reporting": "requested"
  }
}
```

This JSON is not normative yet.

## Practical rollout

### A. Prototype
Build v0.4 locally without changing public v0.3.

### B. Reference implementation
Add experimental v0.4 support to generator and validator.

### C. First real site
Use HangarRC as first migration test.

Success:
```text
site publishes HUMIA
        ↓
agent/tool can discover it
        ↓
AIPREF semantics are not duplicated
        ↓
validator understands the profile
        ↓
site owner can verify the result
```

### D. Second real site
Integrate DataRepublic when its public deployment is ready.

### E. Implementation evidence
Document:
- what was easy;
- what was ambiguous;
- what standards were reused;
- what HUMIA-specific gap was useful;
- what an implementer had to do.

## Success criterion

HUMIA succeeds if a normal site owner can:
1. publish one small cooperation profile;
2. reuse established standards;
3. test the deployment;
4. migrate safely;
5. give an AI agent a clear machine-readable entry point.

The objective is adoption and working implementations, not ownership of every underlying semantic.

## References

- https://datatracker.ietf.org/doc/draft-ietf-aipref-vocab/
- https://datatracker.ietf.org/doc/draft-ietf-aipref-attach/
- https://www.rfc-editor.org/rfc/rfc9309.html
