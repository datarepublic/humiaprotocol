---
title: "HUMIA: A Website-First Protocol for Human-AI Cooperation"
abbrev: "HUMIA Protocol"
docname: draft-treneule-humia-protocol-00
category: exp
ipr: trust200902
submissiontype: IETF
area: Applications and Real-Time

author:
  - ins: B. Treneule
    name: Benjamin Treneule
    org: HUMIA Protocol
    email: contact@humiaprotocol.org
    uri: https://humiaprotocol.org/

date: 2026-08-19

normative:
  RFC2119:
  RFC8174:
  RFC8259:
  RFC8615:
  RFC9110:
  RFC9309:

informative:
  HUMIA-SITE:
    title: HUMIA Protocol
    target: https://humiaprotocol.org/
    author:
      org: HUMIA Protocol

--- abstract

HUMIA defines a website-first mechanism for publishing a machine-readable cooperation policy for AI agents. A website publishes a JSON policy at `/.well-known/humia.json`. The policy identifies the origin and expresses site-level conditions for public-content access, selected AI usage purposes, attribution, and optional usage reporting.

HUMIA does not replace the Robots Exclusion Protocol, authentication, authorization, licensing, or access-control mechanisms. It is an additional cooperation layer. This document also defines an optional, experimental `Humia:` discovery record in `robots.txt` that points HUMIA-aware agents to the canonical policy URI.

--- middle

# Introduction

The Web has a language for crawling. It needs a language for cooperation.

The Robots Exclusion Protocol (REP) {{RFC9309}} provides a widely deployed mechanism for crawlers to understand path-level crawling preferences. Modern AI systems, however, can interact with websites for materially different purposes, including assisting a user, retrieving information for a response, indexing information, bulk collection, or model training.

HUMIA provides a small, origin-level JSON document through which a website can state cooperation conditions for those interactions. The design is intentionally website-first: publication requires only a static JSON file, and no HUMIA account, registration, API, or server-side component is required.

This document defines HUMIA Protocol version 0.3 as an experimental protocol. It intentionally defines a small interoperable core. More advanced mechanisms such as authenticated agent identity, delegated authority, capability negotiation, enforcement, settlement, and signed receipts are outside the scope of this version.

# Conventions and Terminology

The key words **MUST**, **MUST NOT**, **REQUIRED**, **SHALL**, **SHALL NOT**, **SHOULD**, **SHOULD NOT**, **RECOMMENDED**, **NOT RECOMMENDED**, **MAY**, and **OPTIONAL** in this document are to be interpreted as described in BCP 14 {{RFC2119}} {{RFC8174}} when, and only when, they appear in all capitals, as shown here.

Publisher
: The operator responsible for an HTTPS origin and its HUMIA policy.

Agent
: An automated system that retrieves or uses Web resources, including AI-enabled systems acting independently or on behalf of a user.

HUMIA policy
: The JSON representation retrieved from `/.well-known/humia.json` for an origin.

Canonical origin
: The HTTPS origin identified by the `identity.canonical` member of the policy.

Usage purpose
: A declared category describing why an agent intends to use public content.

# Protocol Overview

A HUMIA-aware agent discovers the policy for an HTTPS origin by requesting:

~~~
https://example.com/.well-known/humia.json
~~~

If a valid policy is returned, the agent can use the policy to understand the publisher's declared cooperation policy.

A publisher MAY additionally include the following experimental discovery record in `robots.txt`:

~~~
# HUMIA Protocol discovery (experimental)
Humia: https://example.com/.well-known/humia.json
~~~

The well-known URI is canonical. The `Humia:` record is only a discovery hint and is not required for HUMIA operation.

# Well-Known URI

## URI

For an HTTPS origin with authority `example.com`, the HUMIA policy URI is:

~~~
https://example.com/.well-known/humia.json
~~~

This specification defines HUMIA only for the `https` URI scheme.

The policy applies to the origin from which it is retrieved. A policy retrieved from one origin MUST NOT be interpreted as controlling another origin.

## Retrieval

An agent retrieves the policy using HTTP `GET` {{RFC9110}}.

A successful response:

* MUST use status code `200`;
* MUST have a representation that is valid JSON {{RFC8259}}; and
* SHOULD use media type `application/json`.

Agents SHOULD respect HTTP caching directives. Publishers SHOULD provide cache directives appropriate to the expected frequency of policy changes.

Agents MUST NOT follow a redirect from the HUMIA well-known URI to a different origin. Same-origin redirects MAY be followed in accordance with normal HTTP behavior.

## Absence and Errors

A `404` response means that no HUMIA policy is published at the canonical location.

For any of the following conditions, an agent MUST treat HUMIA as unavailable for that origin:

* a non-successful HTTP response other than a supported same-origin redirect;
* invalid JSON;
* a missing or unsupported `protocol` or `version` member; or
* a canonical identity that does not match the origin from which the policy was retrieved.

HUMIA unavailability MUST NOT be interpreted as permission, prohibition, authorization, or consent. Other applicable mechanisms, contracts, access controls, policies, and law continue to apply.

# JSON Representation

The top-level representation is a JSON object. The following example is illustrative:

~~~ json
{
  "protocol": "HUMIA",
  "version": "0.3",
  "status": "draft",
  "identity": {
    "name": "Example Publisher",
    "canonical": "https://example.com/"
  },
  "access": {
    "public_content": "allow",
    "private_api": "deny"
  },
  "usage": {
    "user_assistance": "allow",
    "search_retrieval": "allow",
    "bulk_crawl": "deny",
    "training": "deny"
  },
  "attribution": {
    "required": true,
    "canonical_url": true
  },
  "reciprocity": {
    "usage_reporting": "requested"
  }
}
~~~

Unknown top-level members and unknown members inside defined objects MUST be ignored by agents unless a future HUMIA version defines otherwise. This permits compatible extension of the representation.

# Required Members

## `protocol`

`protocol` is REQUIRED and MUST be the case-sensitive string `HUMIA`.

## `version`

`version` is REQUIRED. This document defines version string `0.3`.

An agent that does not support the advertised version MUST treat the HUMIA policy as unavailable unless a future specification defines compatible version negotiation.

## `identity`

`identity` is REQUIRED and MUST be a JSON object.

`identity.canonical` is REQUIRED and MUST be an absolute HTTPS URI representing the root of the origin to which the policy applies. The URI origin MUST match the origin from which the policy was retrieved.

`identity.name` is OPTIONAL human-readable text identifying the publisher or website.

# Access Conditions

`access` is OPTIONAL. When present, it MUST be a JSON object.

This version defines two members:

`public_content`
: Declares the publisher's HUMIA condition for publicly reachable content. Values are `allow` or `deny`.

`private_api`
: Declares the publisher's HUMIA condition regarding use of private or non-public APIs. Values are `allow` or `deny`.

These values are declarative cooperation conditions. They do not bypass authentication, authorization, paywalls, network controls, or other technical restrictions.

# Usage Conditions

`usage` is OPTIONAL. When present, it MUST be a JSON object.

This version defines the following usage purposes. Each value is `allow` or `deny`.

`user_assistance`
: Retrieval or reading of public content for the purpose of answering or assisting a specific user request.

`search_retrieval`
: Retrieval, indexing, or referencing of public content for search, discovery, retrieval, grounding, or source-backed responses.

`bulk_crawl`
: High-volume or systematic collection of public content beyond targeted retrieval for a specific user request.

`training`
: Use of retrieved content as training material for a machine-learning model, including pre-training or subsequent model training.

A publisher MAY omit a usage purpose. Omission means HUMIA makes no statement for that purpose. It MUST NOT be interpreted as `allow` or `deny`.

# Attribution

`attribution` is OPTIONAL. When present, it MUST be a JSON object.

`required`
: A boolean. When `true`, the publisher requests attribution when content is used in a context where attribution can reasonably be provided.

`canonical_url`
: A boolean. When `true`, attribution SHOULD preserve or link to the canonical source URL where technically possible.

This document does not define an attribution rendering format.

# Reciprocity

`reciprocity` is OPTIONAL. When present, it MUST be a JSON object.

This version defines one member:

`usage_reporting`
: The string `requested` indicates that the publisher requests usage reporting when the agent supports such reporting.

Version 0.3 does not define a reporting transport, reporting endpoint, mandatory reporting behavior, compensation mechanism, or settlement protocol. `requested` is therefore an informational cooperation request, not an authorization requirement.

# Relationship to robots.txt

HUMIA does not replace REP {{RFC9309}}.

A crawler that is subject to REP MUST continue to evaluate and honor `robots.txt` independently of HUMIA. HUMIA does not grant permission to crawl a path that REP disallows.

Whether REP applies to an agent acting interactively on behalf of a user is outside the scope of this document.

## Experimental `Humia:` Record

A publisher MAY add a line of the following form to `robots.txt`:

~~~
Humia: https://example.com/.well-known/humia.json
~~~

For HUMIA-aware implementations, the record name `Humia` is matched case-insensitively. The value MUST be an absolute HTTPS URI.

The record is an experimental discovery hint only. A HUMIA-aware agent MUST treat the canonical `/.well-known/humia.json` location as authoritative. A `Humia:` value pointing to another origin MUST NOT cause that other origin to control the current origin's HUMIA policy.

Implementations that do not understand the `Humia:` record can ignore it. Its presence does not alter `User-agent`, `Allow`, or `Disallow` processing defined by REP.

# Human-Readable Presentation

A publisher tool MAY present a plain-language explanation of the policy in addition to the machine-readable JSON. Such explanations are non-normative. In case of disagreement, the JSON representation is authoritative for HUMIA processing.

# Security Considerations

HUMIA is a public policy mechanism and MUST NOT contain passwords, bearer tokens, API keys, private credentials, personal authentication material, or other secrets.

A HUMIA policy is not an access-control mechanism. Agents MUST NOT use an `allow` value to bypass authentication, authorization, network restrictions, payment requirements, or other controls.

Publishers should serve HUMIA over HTTPS to protect policy integrity in transit. This specification defines the protocol only for HTTPS origins.

Agents fetching HUMIA policies should apply normal protections against server-side request forgery, malicious redirects, oversized responses, resource exhaustion, and malicious JSON inputs. Cross-origin redirects are prohibited by this specification to reduce origin-confusion risks.

A malicious party that gains control of a publisher's origin can alter the HUMIA policy. HUMIA does not attempt to provide security beyond the security properties of HTTPS and control of the origin in version 0.3.

# Privacy Considerations

Fetching a HUMIA policy can reveal to the publisher that an automated client is interested in the origin, just as fetching other public resources can.

A HUMIA request does not require an agent to disclose an end user's identity. Implementations SHOULD NOT add user-identifying information to HUMIA discovery requests unless another protocol or explicit user authorization requires it.

Publishers SHOULD avoid placing personal data in the HUMIA policy. The policy is intended to be public and broadly cacheable.

# Versioning and Extensibility

The `version` member identifies the HUMIA protocol version used by the representation.

Version 0.3 intentionally defines a small core. Future versions may define additional objects or members, including agent identity, delegated authority, capabilities, interaction mechanisms, verification, enforcement, and richer reciprocity.

Unknown members MUST be ignored as specified in {{json-representation}}. New semantics that would change the meaning of existing members require a new HUMIA version.

# IANA Considerations

This document requests registration in the "Well-Known URIs" registry established by {{RFC8615}}.

URI suffix
: `humia.json`

Change controller
: HUMIA Protocol

Specification document
: This document.

Status
: provisional

Related information
: `https://humiaprotocol.org/`

The registered resource is a JSON representation served over HTTPS with media type `application/json`.

# Implementation Status

This section is non-normative and may be removed before publication as an RFC.

As of August 2026, a public experimental implementation is available at `https://humiaprotocol.org/.well-known/humia.json`, together with a browser-based policy generator that produces a `robots.txt` discovery snippet and a HUMIA JSON policy without requiring an account or backend service.

Additional pilot deployments are being used to evaluate deployment ergonomics and policy semantics. No claim is made that major AI providers currently implement or honor HUMIA.

# Acknowledgements

The author thanks the Web and Internet standards communities whose work on HTTP, JSON, well-known URIs, and the Robots Exclusion Protocol makes this experiment possible.

--- back

# Change Log

## draft-treneule-humia-protocol-00

Initial Internet-Draft version defining HUMIA Protocol v0.3, the `/.well-known/humia.json` resource, the minimal JSON policy model, and the optional experimental `Humia:` discovery record.
