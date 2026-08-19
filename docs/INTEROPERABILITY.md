# HUMIA Protocol — Interoperability and Scope

> Status: non-normative working note — 19 August 2026

HUMIA Protocol is intended to cooperate with existing Web and AI standards rather than replace them.

> **Reuse established standards where they fit. Define new HUMIA semantics only where a clear gap remains.**

## Layer map

| Mechanism | Primary scope | Typical granularity | HUMIA relationship |
|---|---|---|---|
| Robots Exclusion Protocol (REP / `robots.txt`) | Crawler path access | Path / crawler | Complementary. HUMIA does not override REP. |
| IETF AIPREF | Preferences for how content is collected and processed for AI | Content / delivery metadata / protocol attachment | Adjacent and partially overlapping. HUMIA should interoperate rather than duplicate a mature AIPREF vocabulary. |
| HUMIA Protocol v0.3 | Website-first cooperation policy for AI agents: public access conditions, selected usage purposes, attribution, optional reciprocity | HTTPS origin | Current HUMIA core. |
| W3C A2WF | Website-side governance of AI-agent actions and deployment context, including actions, authentication requirements and human oversight | Website / service / action | Adjacent and partially overlapping. Future HUMIA work on actions, auth and human-in-the-loop should align with or reference A2WF rather than duplicate it. |

## 1. Robots Exclusion Protocol

REP answers a crawling question: which paths a crawler may access.

HUMIA does not replace REP and MUST NOT be interpreted as granting permission to crawl a path that REP disallows.

HUMIA adds origin-level cooperation semantics beyond path crawling, such as declared AI usage purposes, attribution and optional reciprocity.

Reference: https://www.rfc-editor.org/rfc/rfc9309.html

## 2. IETF AIPREF

The IETF AI Preferences Working Group is chartered to standardize building blocks for expressing preferences about how content is collected and processed for AI model development, deployment and use. Its work includes a vocabulary and mechanisms for attaching those preferences to content or the protocol that delivers it.

Reference: https://datatracker.ietf.org/wg/aipref/about/

### Boundary with HUMIA

AIPREF is principally **content-preference oriented**.

HUMIA v0.3 is principally an **origin-level cooperation policy**. It currently includes a deliberately small set of usage-purpose fields (`user_assistance`, `search_retrieval`, `bulk_crawl`, `training`) because a site needs a simple deployable policy today.

This creates real overlap.

The preferred direction for future HUMIA versions is therefore:

1. do not invent a competing detailed AI-content-preference vocabulary;
2. evaluate explicit mapping to AIPREF terms as that vocabulary stabilizes;
3. allow HUMIA to act as an origin-level cooperation/discovery layer while AIPREF expresses more specific content-use preferences;
4. avoid defining contradictory precedence rules until an interoperability profile has been tested.

A future HUMIA version may reference or embed standardized AIPREF semantics instead of expanding HUMIA's own usage vocabulary.

## 3. W3C Agent-to-Web Framework (A2WF)

The W3C A2WF Community Group was launched in 2026 to develop machine-readable governance policies for AI agents interacting with websites. Its public scope emphasizes actions beyond content consumption, including filling forms, booking appointments, adding items to carts, submitting orders, authentication requirements and human-in-the-loop verification.

Reference: https://www.w3.org/community/a2wf/

A2WF discussion also describes `siteai.json` as a website-side governance layer capable of distinguishing training, real-time retrieval and agentic interaction. This means the overlap with HUMIA is substantive and should not be hidden.

Reference: https://lists.w3.org/Archives/Public/public-agentprotocol/2026Apr/0005.html

### Boundary with HUMIA

The current practical distinction is:

**HUMIA v0.3 focuses on cooperation around reading and using Web resources:**

- public-content access conditions;
- user assistance;
- search/retrieval;
- bulk crawling;
- training;
- attribution;
- optional usage reporting.

**A2WF is developing broader website governance for agent actions and deployment context:**

- transactional or state-changing actions;
- authentication and authorization requirements;
- human verification / human-in-the-loop rules;
- action-level permissions and governance;
- deployment context and accountability.

This distinction is useful but not absolute. Both projects publish website-side machine-readable policy and both can touch usage semantics.

### HUMIA development rule

Until interoperability work with A2WF is clearer, HUMIA SHOULD NOT add new v0.x fields for:

- form submission;
- purchasing or booking actions;
- authentication requirements;
- delegated authority;
- human approval;
- action-level capability governance.

Those areas should first be evaluated for reuse, reference or mapping to A2WF and related standards.

## 4. Proposed cooperation stack

```text
Web resource
   |
   +-- robots.txt / REP -------- path-level crawling rules
   |
   +-- AIPREF ------------------ content-use preferences
   |
   +-- HUMIA Protocol ---------- origin-level AI cooperation policy
   |
   +-- A2WF -------------------- agent action / governance policy
```

This is a conceptual map, not a claim that these mechanisms are formally layered or currently interoperable.

## 5. Conflict handling — v0.3 guidance

HUMIA v0.3 does not override other protocols, contracts, access controls or law.

For current experimental implementations:

- REP restrictions remain independently applicable to crawling.
- A more specific external content-use preference should not be treated as relaxed merely because HUMIA has a broader `allow` value.
- HUMIA `allow` values are cooperation declarations, not authorization tokens.
- A2WF action governance, where present, should be evaluated independently of HUMIA v0.3.
- When two mechanisms appear inconsistent, an implementation should choose the more conservative behavior and surface the conflict rather than silently infer permission.

These are non-normative deployment guidelines. Formal precedence requires future interoperability work.

## 6. Roadmap implications

For HUMIA Protocol v0.3:

- **freeze the current small core;**
- improve validation and deployment tooling;
- gather implementation experience;
- document live implementations;
- test mappings to AIPREF;
- engage A2WF on division of responsibility and possible cross-references.

For later versions, additions should pass a simple test:

> **Is this capability genuinely missing from REP, AIPREF, A2WF and other relevant standards?**

If the answer is no, HUMIA should reference or interoperate instead of duplicating it.

## 7. Positioning

> **HUMIA Protocol is an experimental, website-first cooperation policy for AI agents. It is designed to complement Web crawling, content-preference and agent-governance mechanisms rather than replace them.**

HUMIA Protocol is currently published as an individual IETF Internet-Draft. Publication does not imply IETF endorsement or standards status.
