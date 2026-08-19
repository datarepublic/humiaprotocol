# HUMIA v0.4 — Experimental Cooperation Profile

**Status:** Experimental working draft  
**Date:** 2026-08-19  
**Public stable baseline:** HUMIA v0.3  
**Deployment status:** Not yet public as a HUMIA manifest

## 1. Purpose

HUMIA v0.4 experiments with a smaller role for HUMIA.

HUMIA does not redefine mechanisms that already have a better home.

The origin-level cooperation profile:

- identifies the website origin;
- points to the standards used by the origin;
- uses REP / robots.txt for crawl-path behavior;
- uses AIPREF for AI content-use preferences;
- leaves protected access to HTTP authentication and authorization;
- keeps only HUMIA-specific cooperation semantics where useful.

## 2. Design principle

HUMIA v0.4 MUST NOT duplicate an external standard merely to provide a
HUMIA-specific spelling for the same semantic.

When an external standard is referenced by the HUMIA profile, that
standard remains authoritative for its own semantics.

A HUMIA profile is not an authentication token, an authorization grant,
a legal licence, or an enforcement mechanism.

## 3. Discovery

The experimental HUMIA discovery endpoint remains:

    /.well-known/humia.json

A website MAY additionally advertise the profile in robots.txt:

    Humia: https://example.com/.well-known/humia.json

This `Humia:` line remains experimental.

## 4. Core object

An experimental v0.4 profile has this high-level shape:

    {
      "protocol": "HUMIA",
      "version": "0.4",
      "status": "experimental",
      "identity": { ... },
      "standards": { ... },
      "attribution": { ... },
      "reciprocity": { ... }
    }

Unknown members SHOULD be ignored by experimental implementations unless
a future specification states otherwise.

## 5. protocol

`protocol` MUST be:

    "HUMIA"

## 6. version

For this experiment, `version` MUST be:

    "0.4"

## 7. status

For the current prototype, `status` MUST be:

    "experimental"

This distinguishes v0.4 experiments from the currently deployed v0.3 draft.

## 8. identity

Example:

    "identity": {
      "name": "Example Site",
      "canonical": "https://example.com/"
    }

`identity.canonical` MUST identify the same HTTPS origin that publishes
the HUMIA profile.

`identity.name` is a human-readable origin/site name.

HUMIA identity is not authenticated agent identity and does not replace
existing Web or security identity mechanisms.

## 9. standards

The `standards` object declares external cooperation mechanisms used by
the origin.

Example:

    "standards": {
      "crawl": {
        "name": "REP",
        "reference": "RFC9309",
        "location": "/robots.txt"
      },
      "ai_usage": {
        "name": "AIPREF",
        "status": "experimental",
        "vocabulary": "draft-ietf-aipref-vocab",
        "discovery": [
          "robots.txt:Content-Usage"
        ]
      }
    }

### 9.1 crawl

`standards.crawl` identifies the mechanism used for crawler path rules.

For this experiment:

- `name` SHOULD be `REP`;
- `reference` SHOULD be `RFC9309`;
- `location` SHOULD be `/robots.txt`.

HUMIA MUST NOT reinterpret REP rules as authentication or authorization.

### 9.2 ai_usage

`standards.ai_usage` identifies AIPREF as the source of AI content-use
preferences.

For this experiment:

- `name` MUST be `AIPREF`;
- `vocabulary` SHOULD identify the AIPREF vocabulary draft family;
- `discovery` lists attachment mechanisms actually used by the origin.

Current experiment example:

    Content-Usage: train-ai=n, search=y

HUMIA v0.4 does not redefine `train-ai` or `search`.

Their meaning comes from AIPREF.

The experimental checker currently interprets:

- `y` as ALLOW;
- `n` as DISALLOW;
- missing or unsupported values as UNKNOWN.

The AIPREF attachment mechanism is still treated as experimental by HUMIA
until the external mechanism is stable enough to reference normatively.

## 10. Protected access

HUMIA v0.4 removes `access.private_api` from the experimental core.

Actual protected/private access belongs to normal HTTP authentication,
authorization, application security, and related mechanisms.

A HUMIA cooperation declaration MUST NOT be interpreted as authorization
to bypass protected access.

## 11. Attribution

Experimental example:

    "attribution": {
      "canonical_url": true
    }

`attribution` remains a HUMIA gap candidate.

For v0.4 experiments:

- `canonical_url: true` expresses that preserving or exposing the original
  canonical source is requested where attribution applies.

This area is intentionally small while overlap with existing provenance,
licensing, citation, and AIPREF semantics is studied.

## 12. Reciprocity

Experimental example:

    "reciprocity": {
      "usage_reporting": "requested"
    }

`reciprocity` expresses cooperation requested from an agent or service in
return for use of the origin's resources.

For this experiment:

- `usage_reporting` MAY be `"requested"`;
- absence means no HUMIA reporting request is declared.

`requested` is not an enforcement mechanism and does not by itself define
a reporting protocol.

Future experiments may define an interoperable reporting mechanism, but
v0.4 MUST NOT invent one until there is a concrete implementation need.

## 13. Removed or delegated v0.3 fields

The following v0.3 concepts are intentionally removed or delegated in the
v0.4 experiment:

- `usage.training` -> AIPREF `train-ai`
- `usage.search_retrieval` -> AIPREF `search`
- `usage.bulk_crawl` -> crawler/REP policy, not downstream AI usage
- `access.private_api` -> HTTP/application authentication and authorization

`usage.user_assistance` has no stable HUMIA v0.4 decision yet and remains
an experiment/gap question rather than a committed core field.

## 14. Migration consistency

During migration from v0.3 to the v0.4 model, implementations MAY publish:

- a public HUMIA v0.3 manifest;
- AIPREF preferences in parallel;
- an experimental v0.4 representation outside the public endpoint.

Where both v0.3 and AIPREF express equivalent semantics, they SHOULD agree.

Current mapping used by the checker:

    HUMIA v0.3 usage.training=allow  -> train-ai=y
    HUMIA v0.3 usage.training=deny   -> train-ai=n

    HUMIA v0.3 usage.search_retrieval=allow -> search=y
    HUMIA v0.3 usage.search_retrieval=deny  -> search=n

A mismatch is an experimental migration failure.

## 15. HangarRC implementation experiment

HangarRC currently demonstrates the migration model.

Public robots.txt:

    Content-Usage: train-ai=n, search=y
    Humia: https://hangarrc.com/.well-known/humia.json

Public HUMIA endpoint:

    version: 0.3

Experimental repository profile:

    version: 0.4

The live checker confirms:

- HUMIA endpoint reachable;
- canonical origin consistent;
- AIPREF `train-ai=n` -> DISALLOW;
- AIPREF `search=y` -> ALLOW;
- v0.3 and AIPREF semantics consistent;
- HUMIA discovery present.

## 16. Experimental conformance

For this prototype, a live origin passes the HUMIA v0.4 interoperability
experiment when:

1. `robots.txt` is reachable;
2. the HUMIA endpoint is reachable;
3. the HUMIA JSON is syntactically valid;
4. protocol identity is HUMIA;
5. canonical origin matches;
6. AIPREF `Content-Usage` is discoverable for the experiment;
7. supported AIPREF values parse correctly;
8. HUMIA robots discovery is correct;
9. when v0.3 equivalents exist, AIPREF and v0.3 semantics are consistent.

This is an implementation experiment, not an IETF certification,
AIPREF certification, legal compliance assessment, or stable HUMIA v0.4
conformance designation.

## 17. Next implementation goals

Before any public v0.4 release:

1. test more than one origin;
2. add negative/error test cases;
3. decide whether `attribution` has a genuine HUMIA-specific role;
4. give `reciprocity` a concrete implementation use case;
5. confirm how AIPREF attachment should be referenced as it evolves;
6. update the generator;
7. update the public validator only after the model stabilizes;
8. provide a documented v0.3 -> v0.4 migration path.

## 18. Non-goals

v0.4 does not currently define:

- agent authentication;
- delegated authority;
- purchases or bookings;
- form submission policy;
- human approval workflows;
- payment or settlement;
- signed receipts;
- enforcement;
- bypass of HTTP authorization;
- a replacement for REP;
- a replacement for AIPREF.
