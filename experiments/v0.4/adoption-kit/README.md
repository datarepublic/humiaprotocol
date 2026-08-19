# HUMIA + AIPREF Pilot Adoption Kit

Status: Experimental pilot
Baseline: public HUMIA v0.3 + experimental AIPREF attachment
Date: 2026-08-19

This kit is for a website owner who wants to run the same interoperability
experiment already used on HangarRC and humiaprotocol.org.

It does NOT claim IETF, AIPREF, or HUMIA v0.4 certification.

## What you publish

Two public resources are required:

1. /.well-known/humia.json
2. /robots.txt

The HUMIA manifest remains v0.3 during this pilot.

The AIPREF `Content-Usage` line is experimental. The AIPREF vocabulary draft
is active, while the current attachment draft used for HTTP/robots discovery
has expired and may change.

## Step 1 — Edit the HUMIA manifest

Open:

    .well-known/humia.json

Replace:

    YOUR SITE NAME
    https://example.com/

Keep the origin HTTPS.

Choose the two usage preferences:

    "training": "allow" or "deny"
    "search_retrieval": "allow" or "deny"

## Step 2 — Edit robots.txt

Open:

    robots.txt

Replace:

    https://example.com/

Then make `Content-Usage` match the HUMIA v0.3 values.

Mapping used by this pilot:

    HUMIA training=allow          -> train-ai=y
    HUMIA training=deny           -> train-ai=n

    HUMIA search_retrieval=allow  -> search=y
    HUMIA search_retrieval=deny   -> search=n

Example:

    Content-Usage: train-ai=n, search=y

## Step 3 — Publish

Publish the files so these URLs return HTTP 200:

    https://YOUR-DOMAIN/.well-known/humia.json
    https://YOUR-DOMAIN/robots.txt

Do not expose private APIs or credentials. HUMIA is not authorization.

## Step 4 — Verify manually

Check:

    curl -fsS https://YOUR-DOMAIN/robots.txt
    curl -fsS https://YOUR-DOMAIN/.well-known/humia.json

## Step 5 — Run the HUMIA experimental checker

From a clone of the HUMIA repository on branch `experiment/v0.4-aipref`:

    python3 experiments/v0.4/check_live.py https://YOUR-DOMAIN

Expected final result:

    RESULT: PASS — HUMIA and AIPREF are discoverable and semantically consistent for this experiment.

## What PASS means

PASS means the experimental checker found:

- a reachable robots.txt;
- a reachable HUMIA manifest;
- valid HUMIA protocol identity;
- matching canonical origin;
- AIPREF `Content-Usage`;
- supported `train-ai` and `search` values;
- HUMIA discovery;
- consistency between the v0.3 HUMIA usage fields and AIPREF preferences.

PASS does NOT mean legal compliance, IETF approval, AIPREF certification,
or stable HUMIA v0.4 conformance.

## Pilot feedback

If a third-party site can install this kit without help from the HUMIA project,
that is an important implementation result. Record:

- origin;
- date;
- published preferences;
- checker output;
- installation problems;
- ambiguities or missing semantics.

Those findings should drive the next HUMIA v0.4 iteration.
