# HUMIA Internet-Draft -00 submission checklist

Draft name: `draft-treneule-humia-protocol-00`

## Before submission

1. Confirm that `contact@humiaprotocol.org` exists and can receive the IETF confirmation email. If not, replace the email in both source files with an address you are willing to publish permanently.
2. Review the author name `Benjamin Treneule` exactly as it should appear publicly.
3. Validate the RFCXML with the IETF Author Tools service or local `xml2rfc`/`rfclint`.
4. Render the XML to TXT and HTML and read both outputs.
5. Fix all blocking validation errors and important nits.
6. Submit the RFCXML at `https://datatracker.ietf.org/submit/` while logged into an IETF Datatracker account if possible.
7. For a new `-00`, complete the author confirmation email if Datatracker requests it.
8. Once posted, use the Datatracker URL as the stable public reference for discussion and later registry review.

## IANA registration target

- Registry: Well-Known URIs
- URI suffix: `humia.json`
- Requested status: provisional
- Change controller: HUMIA Protocol
- Specification: the posted Internet-Draft
- Related information: `https://humiaprotocol.org/`

Do not claim IANA registration until the registry entry actually exists.

## Scope deliberately NOT defined in -00

- authenticated agent identity
- delegated user authority
- capability negotiation
- gateway enforcement
- payment or settlement
- signed receipts
- a mandatory reporting transport

Keeping these out of -00 makes the first draft smaller, auditable, and aligned with what HUMIA v0.3 already deploys today.
