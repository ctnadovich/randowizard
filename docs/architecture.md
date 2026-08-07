# Application Architecture

## Overview

This repository is the application-specific `app/` layer of the live
randonneuring.org CodeIgniter 4 installation. It is not a complete standalone
CodeIgniter project. Framework bootstrap code, routing configuration, Composer
dependencies, public assets, runtime storage, and environment configuration
live in the parent installation.

The application is a server-rendered MVC system with substantial domain logic
in controllers and libraries:

```text
HTTP request
    -> CodeIgniter routing (outside this repository)
    -> BaseController
    -> feature controller
    -> models and domain libraries
    -> HTML, JSON, CSV, or PDF response
```

Its primary purpose is to help regional organizers manage randonneuring events,
routes, rosters, rider check-ins, generated paperwork, published event data,
and signed waivers. It also supplies data and check-in support for eBrevet.

## Repository layout

- `Controllers/` contains request handlers, authorization, feature workflows,
  and most application orchestration.
- `Models/` contains CodeIgniter models and domain-specific database queries.
- `Libraries/` contains route processing, timing, mapping, cryptography, PDF
  generation, waiver services, and the bundled Grocery CRUD implementation.
- `Views/` contains server-rendered PHP fragments and waiver/PDF templates.
- `Helpers/` contains general randonneuring and waiver helper functions.
- `Language/` contains application validation messages.
- `Common.php` is CodeIgniter's application-level extension point but currently
  contains no additional behavior.

Database migrations, schema definitions, tests, and application routes are not
present in this repository.

## Controller hierarchy

`BaseController` is the common foundation for all web controllers. It:

- Initializes the session and shared view data.
- Loads the user and region models.
- Implements login, superuser, and region-administrator checks.
- Centralizes error-page and JSON response helpers.
- Composes complete pages from shared headers, navigation, content fragments,
  and footers.
- Supports region-specific header, footer, and style HTML.

`EventProcessor` extends `BaseController` and is the shared domain foundation
for event-facing features. It combines event, region, roster, check-in, and RUSA
data with Ride with GPS route data. It extracts cues and controls, computes
control times, creates map and eBrevet data, and prepares HTML, JSON, and CSV
representations.

Controllers extending `EventProcessor` include event listings and information,
route management, roster information, check-in reporting, document generation,
publishing, and waivers.

The main controller groups are:

- Public pages and identity: `Home`, `Login`, and `About`.
- Event presentation and APIs: `EventLister`, `EventInfo`, `RosterInfo`, and
  `CheckinStatus`.
- Organizer administration: `EventsCrud`, `PermanentsCrud`, `RegionCrud`,
  `RosterCrud`, `CheckinCrud`, and `MemberCrud`.
- Route and paperwork workflows: `RouteManager`, `Generate`, and
  `PublishPaperwork`.
- eBrevet integration: `PostCheckin`, check-in status endpoints, and generated
  cryptographic control codes.
- Waiver capture and retrieval: `Waiver`.
- Maintenance and supporting endpoints: `Utility` and `RegionLister`.

The administration controllers use the bundled `GroceryCrud` library to build
database management screens. They apply login and region authorization before
rendering records.

## Models and persistence

The models wrap a relational database schema. Important represented tables are:

- `event`
- `region`
- `user`
- `rba`, associating users with authorized regions
- `roster`
- `checkin`
- `rusa`, used partly as a local membership cache
- `waiver`
- `event_waiver_context`
- `waiver_access_log`

Most models expose focused queries and state transitions rather than rich domain
objects. Database rows are normally passed through the application as
associative arrays. The code uses a mixture of CodeIgniter's model/query-builder
API and handwritten SQL joins.

An event's public identifier is constructed from its controlling ACP club code
and local database ID:

```text
<ACP club code>-<local event ID>
```

The waiver models have a more explicit state model. Waiver sessions can be
pending, completed, expired, or cancelled. Immutable event details are stored
separately from participant sessions, and access to waiver resources is logged.

## Domain libraries

The major domain services are:

- `Rwgps`: downloads, caches, validates, and parses Ride with GPS route JSON.
- `Controletimes`: computes brevet control opening and closing times.
- `Units` and `Map`: provide unit conversion and map/elevation-profile output.
- `Crypto`: generates eBrevet start, control, and finish codes.
- `Myfpdf`: provides shared FPDF extensions.
- `Cuesheet`, `Brevetcard`, `Signin`, `Postcard`, and `OldWaiver`: generate PDF
  paperwork.
- `WaiverContext`: constructs and validates normalized local and external waiver
  data.
- `WaiverTemplate`: validates and interpolates waiver templates.
- `WaiverStorage`: immutably stores and retrieves completed waiver documents.
- `IndemnifiedParty`: resolves parties named by waiver templates.
- `GroceryCrud`: provides the administrative CRUD framework.

Ride with GPS route data is cached under the parent installation's public assets
directory. Traditional event paperwork is generated primarily with FPDF; the
newer signed-waiver workflow uses Dompdf.

## Views and page composition

The application uses server-rendered PHP views. Reusable fragments include the
site head, navigation, footers, event panels and tabs, route and roster tables,
region-specific branding, authentication forms, and Grocery CRUD wrappers.

This is not a strict one-controller-to-one-template design. `BaseController`
concatenates several view fragments into a response, and some controllers build
HTML snippets before passing them into panel views. Shared template state is
accumulated in `BaseController::$viewData`.

## Core event flow

```text
event database row
    + region and timezone configuration
    + cached Ride with GPS route
    + roster and check-in data
    -> EventProcessor
    -> controls, cues, times, maps, warnings, and rider data
    -> event pages, JSON, CSV, generated PDFs, or published paperwork
```

`Generate` dispatches to specialized private generators after `EventProcessor`
has assembled and validated the event data. `PublishPaperwork` manages
publication and recaching.

## Authentication and authorization

Authentication is session-based. Passwords use PHP's password hashing and
verification APIs. Session state records the current user, superuser status, and
authorized regions.

Organizer access is scoped by the `rba` relationship between users and regions.
Administrative controllers filter records to those regions unless the user is a
superuser. Public event, check-in, and waiver endpoints use feature-specific
validation rather than requiring an organizer session in every case.

## Waiver subsystem

The waiver feature is a relatively self-contained subsystem supporting both
locally managed riders and external registration systems:

```text
local roster entry or authenticated external JSON request
    -> WaiverContext
    -> immutable event context and participant waiver session
    -> HTML signature form
    -> validation and finalization
    -> immutable signed PDF, SHA-256 metadata, and access log
    -> completion page, PDF endpoint, or JSON reference endpoint
```

External callers authenticate with a region API key and can create signing
sessions, redirect participants to the waiver form, and later retrieve the
signed document or reference metadata. The workflow is documented in
`README_WAIVER_API.md`.

## Architectural characteristics

This is a pragmatic, controller-oriented CodeIgniter application rather than a
strictly layered domain architecture:

- Controllers are the primary orchestration layer and can be large.
- Models encapsulate tables and common queries but normally return arrays.
- Libraries contain the most specialized computation and document generation.
- Views are composed from reusable server-rendered fragments.
- Some infrastructure paths and external URLs are embedded directly in domain
  libraries.
- Routes, framework filters, dependency definitions, and runtime configuration
  must be assessed in the parent CodeIgniter installation, not this repository.
