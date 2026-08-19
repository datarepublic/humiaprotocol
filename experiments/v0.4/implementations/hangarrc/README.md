# HangarRC — HUMIA + AIPREF implementation experiment

Date: 2026-08-19
Origin: https://hangarrc.com/
Status: Live experiment

## Purpose

HangarRC is the first live website used to test HUMIA together with AIPREF.

The objective is to avoid duplicating AI content-use preferences inside HUMIA.

## Live discovery

HangarRC currently publishes:

    User-agent: *
    Content-Usage: train-ai=n, search=y

    Humia: https://hangarrc.com/.well-known/humia.json

Interpretation:

- train-ai=n: AI training use is disallowed.
- search=y: search use is allowed.
- Humia: points agents to the HUMIA cooperation profile.

## Architecture tested

    HangarRC
       |
       +-- robots.txt / REP
       |     crawl rules
       |
       +-- Content-Usage / AIPREF
       |     AI content-use preferences
       |
       +-- HUMIA
             origin-level cooperation profile
             attribution
             reciprocity

## Compatibility

The public HUMIA endpoint remains v0.3 during this experiment.

The v0.4 representation stored in this repository is experimental and is
not yet deployed as the public HUMIA manifest.

This allows AIPREF integration to be tested without breaking the existing
validated HUMIA v0.3 deployment.

## Result

The experiment demonstrates that a real website can simultaneously publish:

1. robots.txt crawl rules;
2. AIPREF AI usage preferences;
3. HUMIA discovery;
4. an existing HUMIA v0.3 cooperation profile.

This is the first HUMIA + AIPREF live implementation experiment.
