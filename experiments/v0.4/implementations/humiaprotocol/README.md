# HUMIA Protocol — HUMIA + AIPREF implementation experiment

Date: 2026-08-19
Origin: https://humiaprotocol.org/
Status: Live experiment

## Live discovery

The origin currently publishes:

    User-agent: *
    Allow: /
    Content-Usage: train-ai=n, search=y

    Humia: https://humiaprotocol.org/.well-known/humia.json

The public HUMIA manifest remains version 0.3.

## Semantic result

The experimental live checker reports:

    train-ai=n -> DISALLOW
    search=y   -> ALLOW

These values are consistent with the current HUMIA v0.3 manifest:

    usage.training=deny
    usage.search_retrieval=allow

## Result

HUMIA and AIPREF are simultaneously discoverable and semantically
consistent on this live origin.

This is an experimental implementation result, not an IETF, AIPREF,
or standards certification.
