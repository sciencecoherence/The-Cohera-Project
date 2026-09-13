# sciencecoherence-blog — Build Brief

*Brief for the standalone blog project at `~/Documents/research/sciencecoherence-blog/`. Read this AFTER reviewing the existing template files (index.html, entry.html, protocol.html, styles.css) and the main sciencecoherence-site WEBSITE-STATUS.md and DESIGN.md.*

---

## What this is

A standalone blog that **blends two existing design languages** to host two distinct content types, with consistent visual identity that connects back to the main sciencecoherence.com site.

### The two pre-existing design languages

**1. BASELINE.LOG** — the current blog template (in this repo)
- Brutalist, monospace (JetBrains Mono / system mono)
- Warm paper background (`#f4f1ea`) — close to the main site
- Dark ink (`#0a0a0a`)
- Sharp borders, no shadows, ASCII rule separators
- All caps for navigation and metadata
- Tabular numbers, dense data tables, key-value blocks
- Entry cards with date stamps (date + entry number + day count)
- Designed for: **daily protocol logs with metrics, photos, intake data**
- Three existing pages: `index.html` (feed), `entry.html` (full daily entry), `protocol.html` (the protocol spec)

**2. Main site (sciencecoherence-site)** — the literary aesthetic
- Cormorant Garamond + Crimson Pro (serif)
- Warm paper background (`#F8F6F1`) — close to BASELINE.LOG
- Dark ink (`#14151A`)
- Soft gradient section dividers, generous whitespace
- Sentence-case headings, italic emphasis
- Single-column 640px reading-first layout
- Designed for: **long-form essays, framework articulation**

### The two content types the blog must host

**A. Daily protocol logs** — what the existing BASELINE.LOG template is built for. Short, data-dense, dated. Metrics, photos, intake records, mood/sleep markers. Brutalist-monospace aesthetic fits this content natively.

**B. Transmissions** — long-form voice-originated essays like *The 25 June Transmission* (source: `transmission-25june-2026-public.md`). Personal, vulnerable, philosophical. Spiritual register. Sectioned with Roman numerals. Sectioned by mood, not by data. Brutalist-monospace aesthetic would clash with this content — it needs serif typography, slower rhythm, generous breathing room.

---

## The central design problem

**How do two opposing aesthetics (brutalist data-log vs literary essay) coexist in one blog without feeling like two separate sites stapled together?**

The blend has to happen somewhere. Three possible strategies — the new chat should help the user pick:

### Strategy 1 — Two content types, two aesthetics, shared shell

- **Shared:** nav, footer, color tokens (warm paper + dark ink), the `BASELINE.LOG` brand mark in the header
- **Per content type:** Logs use full BASELINE.LOG aesthetic (monospace, data tables, sharp borders). Transmissions use the main site aesthetic (serif, single-column reading, soft dividers).
- **Distinction:** the moment a reader clicks on a transmission vs a log, the typographic register signals the content type. No confusion.
- **Risk:** can feel inconsistent on landing pages where both types appear in the same feed.

### Strategy 2 — One blended aesthetic for everything

- Pick the BASELINE.LOG color palette and chrome (warm paper, dark ink, sharp borders, monospace metadata)
- Use serif (Cormorant + Crimson) for prose body in ALL post types
- Monospace stays for: dates, entry numbers, stats rows, micro-metadata, nav items, footer
- Serif for: headlines, body text, italic emphasis, callouts
- This is the literal blend the user asked for — both languages present in every post, doing different jobs.
- **Risk:** if the proportions are wrong, it can feel muddled.

### Strategy 3 — Two separate "modes" the reader switches between

- The blog has a "Logs" section (full BASELINE.LOG) and a "Transmissions" section (full main-site aesthetic)
- Each section feels like its own micro-site
- They're connected by a single homepage that has both feeds
- **Risk:** feels less like a blog and more like a collection of sub-projects.

**Recommendation to user:** Strategy 2 (one blended aesthetic) is the most ambitious and the most likely to read as a single coherent design with a real signature. The blend is the differentiation. Strategy 1 is the safer fallback.

---

## Architecture

### Folder location

`~/Documents/research/sciencecoherence-blog/` — standalone repo (separate from sciencecoherence-site)

### Existing files (do NOT discard)

- `index.html` — current feed of daily protocol log entries
- `entry.html` — full daily entry detail (with intake table, photo grid, metrics, key-value spec block)
- `protocol.html` — the protocol documentation (similar in scope to sciencecoherence-site/time-crystalline-protocol.html)
- `styles.css` — the BASELINE.LOG brutalist stylesheet (7.5KB, well-structured with CSS custom properties)

### Files to add

- `transmissions/` folder for long-form transmissions
  - `2026-06-25-the-25-june-transmission.html` — first transmission
  - Future: `YYYY-MM-DD-slug.html` for additional transmissions
  - `source/` subfolder for `.md` originals
- `transmissions.html` — index page listing all transmissions
- A revised `index.html` that becomes the blog HOMEPAGE (combining logs + transmissions feeds, or being a hub that links to both)
- Decision pending: whether `index.html` becomes a unified feed or a hub. See "Homepage decision" below.

### Final folder structure (target state)

```
sciencecoherence-blog/
├── index.html                                          (unified feed OR hub homepage)
├── protocol.html                                       (existing — keep)
├── logs/                                               (move daily entries here)
│   ├── index.html                                      (logs feed)
│   ├── 2026-06-08-entry-001.html
│   ├── 2026-06-09-entry-002.html
│   ├── 2026-06-10-entry-003.html                       (was entry.html)
│   └── source/                                         (markdown sources if any)
├── transmissions/
│   ├── index.html                                      (transmissions feed)
│   ├── 2026-06-25-the-25-june-transmission.html
│   └── source/
│       └── 2026-06-25-the-25-june-transmission.md
├── styles.css                                          (extended for blend)
└── (optional) css/transmissions.css                    (page-specific if Strategy 1)
```

---

## Relationship to the main sciencecoherence.com site

The blog is a **standalone project** but should feel related to the main site:

- **Cross-link in main site nav:** add a small "Blog" or "Log" link in the main site's nav or footer that points to the blog
- **Cross-link in blog nav:** add a "← sciencecoherence.com" link that points back to the main site
- **Shared brand:** the BASELINE.LOG header could acknowledge the parent (e.g., "BASELINE.LOG · A sciencecoherence.com log")
- **Shared color tokens:** both use the warm-paper + dark-ink palette. Already mostly aligned.

### Domain decision (deferred)

For deployment, options:
- Subdomain: `log.sciencecoherence.com` or `blog.sciencecoherence.com`
- Path: `sciencecoherence.com/blog/` (would require copying the blog folder into the main site repo, or build-step integration)
- Standalone domain: `baseline.log` or similar (more committed, costs $)

Don't decide now. Build locally first, deploy decision comes later.

---

## Homepage decision (Strategy-dependent)

If Strategy 1 (two-aesthetic, shared shell):
- `index.html` = a hub page with two sections: "Recent Logs" (3–5 entries, BASELINE styling) and "Recent Transmissions" (3–5 entries, serif styling). Reader picks which mode to enter.

If Strategy 2 (blended aesthetic everywhere):
- `index.html` = a unified feed. All post types in chronological order with type-tags ("LOG", "TRANSMISSION") in monospace metadata. Body of each card uses serif for any prose excerpt, monospace for metrics.

If Strategy 3 (separate modes):
- `index.html` = a minimal hub. Two doors: "Enter Logs" and "Enter Transmissions". Each leads to its own micro-site.

---

## Per-content-type page structure

### Logs (keep BASELINE.LOG full aesthetic)

The existing `entry.html` is the template. Structure:
- Header (BASELINE.LOG brand, nav)
- Meta bar (entry number, date, status)
- H1 with date + entry number
- Header snapshot KV block (date, sleep, wake, location, solar noon)
- Photo log grid
- Intake table
- Other metric tables (sleep, HRV, mood, etc.)
- Notes / observations callout
- Footer

Each new daily log follows this template. Naming: `logs/YYYY-MM-DD-entry-NNN.html`.

### Transmissions (new — serif/literary register)

Based on the transmission source file structure:
- Header with BASELINE.LOG brand (consistent across blog) but adapted typographically
- Meta bar in monospace (date, location, transmission number, status)
- H1: the transmission title (serif Cormorant Garamond, NOT all-caps)
- Origin note: italic Cormorant, smaller (where received, when, how)
- Sectioned body with Roman numeral breaks (`## I.`, `## II.`, etc.)
- Closing date stamp in italic monospace
- Cross-page nav (previous/next transmission, return to transmissions index)
- Footer

The DESIGN PROBLEM is in this transmission template: how much BASELINE.LOG chrome do you keep, and how much do you swap for serif/literary treatment? That's the call the new chat needs to make with the user.

---

## Voice notes

**Logs:** factual, data-first, neutral. Inputs in, markers out. No theory. (Per the existing template's tagline.) Voice is matter-of-fact, observational.

**Transmissions:** first-person, intimate, vulnerable, sometimes spiritual. Voice-originated. The user's actual voice — including its imperfections, self-doubt, raw turns of phrase. **No editorial cleanup beyond grammar/typo fixes.** Do not "professionalize" transmissions.

The blog can hold both voices without choosing — that's part of the point. Logs are body data; transmissions are mind/spirit channel. Both are reports from the same person.

---

## Constraints inherited from main site

- **WCAG AAA on body text** — verify contrast for any new color combinations
- **No splatter** — each post lives at one canonical URL
- **Every link earns itself** — sparing cross-links between posts
- **Preserve user's voice** — no professionalization of transmissions

New constraint specific to the blog:

- **Honor BASELINE.LOG's data-density aesthetic for logs** — don't soften it with serif typography or rounded borders. The brutalist treatment is the point for protocol logs.

---

## Order of build operations

For the new dedicated blog chat to execute:

1. Read all existing files (index.html, entry.html, protocol.html, styles.css) and this brief and the transmission source.
2. Summarize understanding + flag the central design problem. Pause for user input on Strategy 1/2/3.
3. Once strategy is picked, propose a design treatment for the transmission template (what stays brutalist, what becomes serif).
4. Pause for user approval on the design treatment.
5. Implement the chosen strategy:
   - Update `styles.css` (or add `css/transmissions.css`) with the new style rules
   - Build `transmissions/2026-06-25-the-25-june-transmission.html` from the source
   - Build `transmissions/index.html` (transmissions feed)
   - Refactor or restructure `index.html` (homepage) per strategy
   - Move existing daily entries into `logs/` folder + create `logs/index.html`
6. Test all pages render correctly + nav consistency.
7. Decide cross-link approach with main site (add link from main, add link back to main).
8. Commit + push.

Approximately 3–5 chat sessions to full build. Don't try to do it in one pass.

---

## Working environment

Same as the main website project:
- **Editor:** VS Code
- **AI assistant:** Claude Code (inside VS Code)
- **Cowork chats:** for design decisions, content writing, strategy
- **Local feedback loop:** edit → save → refresh browser
- **Git operations:** Git Bash, research SSH alias (`github.com-research`)
- **User is a beginner programmer** — keep technical explanations concrete, avoid jargon

---

## Key questions for the user (raise these in the first chat)

1. **Which blend strategy?** (1, 2, or 3 from above — recommendation: Strategy 2)
2. **What does the homepage do?** (Hub with two feeds, unified chronological feed, or minimal door-picker?)
3. **What's the blog called publicly?** ("BASELINE.LOG" as in current template, or rename to something that fits both content types? "Transmissions from the baseline"? "The Field Log"? Or keep BASELINE.LOG and accept that transmissions live "in the log" as a different entry type?)
4. **How does it connect to the main site?** (Subdomain, path, standalone domain — deferred for deploy phase but worth airing early)
5. **Any other content types planned?** (Just logs and transmissions, or also: research notes, book reviews, voice memos, etc.?)

Don't build until these are answered.
