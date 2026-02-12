# PHPUnit Test Plan: 80% Code Coverage

## Goal

Achieve 80% code coverage with a mix of unit tests and integration tests. Focus on significant payload coverage across classes, variables, and breakpoints.

---

## Current State

| Area | Test Files | Coverage |
|------|------------|----------|
| Variables convertors | 13 files | Color_Hex, Color_Rgb, Color_Rgba, Color_Hsl, Color_Hsla, Color_Named, Percentage, Length_Size_Viewport, Font_Family, Line_Height, Opacity, Css_Function, Unsupported_Font_Variable_Service |
| Variables pipeline | 4 files | Variable_Extractor, Variable_Conversion_Service, Variable_Convertor_Registry, Variable_Fallback_Substitutor |
| Classes | 3 files | Class_Extractor, Class_Conversion_Service, Converter_Registry |
| HTML converter | 3 files | Atomic_Data_Parser, Html_Converter, Widget_Styles_Integrator |
| CSS / Breakpoints | 4 files | Breakpoint_Matcher, Media_Query_Parser, Id_Style_Extractor, Css_Converter |
| REST APIs | 0 | None |
| Import | 0 | None |

**Total**: ~251 unit tests, 16 skipped (require Elementor). Phase 2 complete (variables, CSS, classes, HTML).

---

## Phase 1: Test Infrastructure

### 1.1 Fixtures Directory

Create `tests/phpunit/fixtures/` with reusable payload files:

```
tests/phpunit/fixtures/
├── classes/
│   ├── single-class-simple.json
│   ├── multiple-classes-on-element.json
│   ├── nested-elements.json
│   ├── breakpoint-desktop-tablet-mobile.json
│   ├── grid-template-custom-css.json
│   └── flexbox-with-gap.json
├── variables/
│   ├── root-simple.json
│   ├── root-multiple-types.json
│   ├── color-hex-rgb-hsl.json
│   ├── font-family-generic.json
│   ├── length-rem-em-px.json
│   ├── line-height-unitless.json
│   └── css-function-calc.json
├── breakpoints/
│   ├── media-max-width-exact.json
│   ├── media-max-width-within-tolerance.json
│   ├── media-multiple-breakpoints.json
│   ├── unmatched-breakpoint-skipped.json
│   └── id-rules-per-breakpoint.json
└── integration/
    ├── full-import-payload.json (from test-breakpoints-payload.json)
    └── responsive-hero.json (from test-responsive-scraper.json)
```

### 1.2 Integration Test Bootstrap

Create `tests/phpunit/bootstrap-integration.php` that:

- Loads WordPress test env via `WP_TESTS_DIR`
- Activates Elementor and this plugin
- Bootstraps `Elementor\Plugin` and kit
- Provides helper to create posts, get REST responses

Requires `bin/install-wp-tests-local.sh` (copy from Elementor or hello-plus) and `composer test:install` script.

### 1.3 Coverage Configuration

Add to `phpunit.xml.dist`:

```xml
<coverage>
  <report>
    <clover outputFile="coverage-report/clover.xml"/>
    <html outputDirectory="coverage-report/html"/>
  </report>
  <include>
    <directory suffix=".php">./includes</directory>
  </include>
  <exclude>
    <directory>./includes/autoloader.php</directory>
    <file>./elementor-html-css-converter.php</file>
  </exclude>
</coverage>
```

Add `composer run coverage` script using `phpdbg` or `xdebug`.

---

## Phase 2: Unit Tests (Expand Coverage)

### 2.1 Variables Convertors (Complete)

| Convertor | File | Priority | Key Cases |
|-----------|------|-----------|-----------|
| Color_Hex_Variable_Convertor | test-color-hex-variable-convertor.php | P1 | #fff, #ffffff, #fff8, shorthand |
| Color_Rgb_Variable_Convertor | test-color-rgb-variable-convertor.php | P1 | rgb(), rgba() |
| Color_Hsl_Variable_Convertor | test-color-hsl-variable-convertor.php | P1 | hsl(), hsla() |
| Color_Named_Variable_Convertor | test-color-named-variable-convertor.php | P1 | red, transparent |
| Color_Mix_Variable_Convertor | test-color-mix-variable-convertor.php | P2 | color-mix() |
| Percentage_Variable_Convertor | test-percentage-variable-convertor.php | P1 | %, unitless rejection |
| Length_Size_Viewport_Variable_Convertor | test-length-size-viewport-variable-convertor.php | P1 | rem, em, px, vw, vh |

### 2.2 Variables Pipeline (New)

| Class | File | Priority | Key Cases |
|-------|------|----------|-----------|
| Variable_Extractor | test-variable-extractor.php | P1 | :root parsing, multiple vars, fallbacks |
| Variable_Conversion_Service | test-variable-conversion-service.php | P1 | resolve + convert, fallback substitution |
| Variable_Convertor_Registry | test-variable-convertor-registry.php | P2 | get_convertor, supports |
| Variable_Fallback_Substitutor | test-variable-fallback-substitutor.php | P2 | var(--x, fallback) handling |

### 2.3 CSS Convertors (New)

| Class | File | Priority | Key Cases |
|-------|------|----------|-----------|
| Breakpoint_Matcher | test-breakpoint-matcher.php | P1 | exact match, closest within 200px, no match, empty config |
| Media_Query_Parser | test-media-query-parser.php | P1 | max-width, min-width, screen and |
| Id_Style_Extractor | test-id-style-extractor.php | P1 | #id rules, media blocks, breakpoint grouping |
| Css_Converter | test-css-converter.php | P1 | convert_properties, custom_css split |
| Style_Definition_Builder | test-style-definition-builder.php | P2 | build_with_breakpoints |

Property converters: prioritize Color, Padding, Margin, Display, Gap, Flex (high usage). Others as time allows.

### 2.4 Classes Pipeline (New)

| Class | File | Priority | Key Cases |
|-------|------|----------|-----------|
| Class_Extractor | test-class-extractor.php | P1 | extract from CSS, class vs ID, media blocks |
| Class_Conversion_Service | test-class-conversion-service.php | P1 | convert_to_atomic, breakpoint_props |
| Converter_Registry | test-converter-registry.php | P2 | get_converter, supports |

### 2.5 HTML Converter Pipeline (New)

| Class | File | Priority | Key Cases |
|-------|------|----------|-----------|
| Atomic_Data_Parser | test-atomic-data-parser.php | P1 | parse HTML+CSS, id_rules, breakpoint_props |
| Html_Converter | test-html-converter.php | P1 | convert full document |
| Widget_Styles_Integrator | test-widget-styles-integrator.php | P2 | integrate_styles_into_widget |
| Id_Style_Extractor | (see 2.3) | - | - |

---

## Phase 3: Integration Tests

### 3.1 REST API Integration

Requires WP + Elementor bootstrap. Create `tests/phpunit/integration/`:

| Endpoint | File | Payload Focus |
|----------|------|---------------|
| POST /convert-html | test-convert-html-endpoint.php | Full payload: classes + variables + breakpoints |
| POST /import-variables | test-import-variables-endpoint.php | :root CSS, update_mode |
| GET /breakpoints | test-breakpoints-endpoint.php | Response shape, enabled breakpoints |
| POST /trigger-import | test-trigger-import-endpoint.php | Payload structure, breakpoints in client_payload |
| GET /css-to-atomic | test-css-to-atomic-endpoint.php | Classes payload with breakpoints |

### 3.2 Payload Study Tests

Create dedicated tests that assert on real payload outputs:

| Test File | Purpose |
|-----------|---------|
| test-classes-payload-output.php | Given class payloads, assert atomic_props + custom_css structure |
| test-variables-payload-output.php | Given :root payloads, assert variable creation and resolution |
| test-breakpoints-payload-output.php | Given media query payloads, assert breakpoint_props keys (desktop, tablet, mobile) |
| test-combined-payload-output.php | Full HTML+CSS with IDs, classes, :root, media; assert widget tree + styles |

### 3.3 Fixture-Driven Tests

For each fixture in `tests/phpunit/fixtures/`:

1. Load JSON
2. Call converter or REST endpoint
3. Assert no exceptions, expected keys in output
4. Optionally snapshot critical fields (if using spatie/phpunit-snapshot-assertions)

---

## Phase 4: Breakpoint Payload Matrix

Study breakpoints with a matrix of payloads:

| Breakpoint Config | CSS Media | Expected Elementor Breakpoint |
|-------------------|-----------|-------------------------------|
| Default (1024, 767) | max-width: 1024px | tablet |
| Default | max-width: 767px | mobile |
| Default | max-width: 880px | closest (tablet if within 200px) |
| Default | max-width: 500px | mobile or null if too far |
| Custom tablet 1200 | max-width: 1200px | tablet |
| Unmatched 2000px | max-width: 2000px | null (skipped) |

Payload fixtures should cover:

- Single breakpoint (desktop only)
- Desktop + tablet
- Desktop + tablet + mobile
- Extra breakpoints (laptop, widescreen) if enabled
- Unmatched media (ensure no crash, styles skipped)
- Mixed ID and class with breakpoints
- Grid/flex with breakpoint-specific layout changes

---

## Phase 5: Execution Order

1. **Week 1**: Phase 1 (fixtures, bootstrap-integration, coverage config)
2. **Week 2**: Phase 2.1 + 2.2 (variables convertors and pipeline)
3. **Week 3**: Phase 2.3 + 2.4 (CSS + classes)
4. **Week 4**: Phase 2.5 (HTML) + Phase 3.1 (REST integration)
5. **Week 5**: Phase 3.2 + 3.3 (payload study, fixture-driven) + Phase 4 (breakpoint matrix)

---

## Coverage Targets by Directory

| Directory | Target | Notes |
|-----------|--------|-------|
| includes/converters/variables/ | 90% | Core logic, many pure functions |
| includes/converters/css/ | 80% | Breakpoint_Matcher, Css_Converter, key properties |
| includes/converters/classes/ | 75% | REST wiring, Class_Conversion_Service |
| includes/converters/html/ | 70% | Complex, integration helps |
| includes/converters/import/ | 60% | REST + external calls, mock where needed |
| includes/converters/images/ | 50% | Network/IO, mock in unit tests |

---

## Commands

```bash
composer test                    # Unit tests only (no WP)
composer test:integration        # Integration tests (requires test:install)
composer test:install            # Install WP test lib + Elementor (interactive)
composer run coverage            # Generate coverage report
```

**First time setup:** See [phpunit-setup.md](phpunit-setup.md) for detailed instructions, especially for Local by Flywheel configuration.

---

## References

- Existing payloads: `test-breakpoints-payload.json`, `test-responsive-scraper.json`
- README Test Payloads section for canonical examples
- Elementor `bin/install-wp-tests-local.sh` for integration setup
- hello-plus / elementor phpunit.xml for WP bootstrap pattern
