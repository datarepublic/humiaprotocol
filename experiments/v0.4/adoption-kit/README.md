# HUMIA + AIPREF Pilot Adoption Kit

Status: Experimental pilot
Baseline: public HUMIA v0.3 + experimental AIPREF attachment
Date: 2026-08-19

This kit is intended to test whether an independent website operator can
publish HUMIA + AIPREF without assistance from the HUMIA project.

It does NOT claim IETF, AIPREF, or HUMIA v0.4 certification.

## Important robots.txt safety rule

DO NOT replace an existing robots.txt with a HUMIA template.

Existing websites often have crawler rules, sitemap declarations, or
application-specific exclusions that must be preserved.

This kit therefore generates a robots ADDITIONS file. The operator merges the
generated lines into the existing robots.txt after reviewing the current file.

For the AIPREF experiment, the Content-Usage rule belongs in the intended
robots group, usually the existing `User-agent: *` group for a site-wide pilot.

## 1. Generate the pilot files

Run:

    python3 configure.py \
      --origin https://example.com \
      --name "Example Site" \
      --training deny \
      --search allow \
      --output generated

Allowed values for `--training` and `--search` are:

    allow
    deny

The generator creates:

    generated/.well-known/humia.json
    generated/robots-additions.txt

## 2. Review the generated manifest

The public HUMIA manifest remains v0.3 during this pilot.

The generator maps:

    training=allow -> train-ai=y
    training=deny  -> train-ai=n

    search=allow   -> search=y
    search=deny    -> search=n

## 3. Merge robots additions safely

First inspect the site's current robots file:

    curl -fsS https://example.com/robots.txt

Then open:

    generated/robots-additions.txt

Merge the `Content-Usage` line into the intended robots group.

Preserve all existing Allow, Disallow, Sitemap, and other site-specific rules.

Add the experimental HUMIA discovery line without deleting existing content.

## 4. Publish

Publish:

    generated/.well-known/humia.json

as:

    https://YOUR-DOMAIN/.well-known/humia.json

Publish the reviewed robots changes as the site's normal:

    https://YOUR-DOMAIN/robots.txt

Do not expose credentials or protected APIs. HUMIA is not authorization.

## 5. Verify the live files

Run:

    ./verify.sh https://YOUR-DOMAIN

Then, from a HUMIA repository checkout on branch `experiment/v0.4-aipref`:

    python3 experiments/v0.4/check_live.py https://YOUR-DOMAIN

Expected final result:

    RESULT: PASS — HUMIA and AIPREF are discoverable and semantically consistent for this experiment.

## What PASS means

PASS means the experimental checker found the public resources and confirmed
the currently supported HUMIA v0.3 <-> AIPREF semantic mapping.

PASS does NOT mean legal compliance, IETF approval, AIPREF certification,
or stable HUMIA v0.4 conformance.

## Pilot feedback to record

For every independent installation, record:

- origin;
- date;
- training preference;
- search preference;
- checker output;
- existing robots structure;
- installation problems;
- ambiguities;
- anything the operator needed HUMIA project help to understand.

A successful independent installation is stronger evidence than another
installation performed by the HUMIA project itself.
