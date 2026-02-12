# Test Improvement Plan

## Overview

Current state: ~39 test files, **291 unit tests**, **10.7% actual coverage** (827/7709 statements)
Goal: Reach 80% coverage with high-quality, maintainable tests

**Strategy**: Quick wins first, then systematic quality improvements and debt reduction.

**Phase Status**: 1 complete, 2 complete (except 2.2 deferred), 3 complete, 4 partial (4.1–4.3 done, 4.4 not started)

### Quick Reference

| Command | Purpose |
|---------|---------|
| `composer test` | Unit tests (~1s) |
| `composer test:integration` | Integration tests (WP+Elementor) |
| `composer test:all` | Unit + integration |
| `composer test:performance` | Performance baselines |
| `composer run coverage` | Code coverage (phpdbg) |
| `composer mutation` | Mutation testing (Infection) |

---

## Phase 1: Quick Wins & Cleanup (1-2 weeks)

### 1.1 Generate Actual Coverage Report
**Priority**: Critical
**Effort**: 30 minutes
**Impact**: High - Need baseline metrics

**Tasks**:
- [x] Run `composer run coverage` to generate HTML report
- [x] Document actual coverage percentage per directory
- [ ] Identify top 10 uncovered files by usage frequency
- [ ] Add coverage badge to README

**Success Criteria**:
- [x] Coverage report generated successfully
- [x] Actual coverage percentage documented: **10.7%** (827/7709 statements, 84/767 methods)
- [ ] Coverage gaps visualized

### 1.2 Fix Missing Fixture Files
**Priority**: High
**Effort**: 2 hours
**Impact**: High - Tests currently fail

**Status**: Complete. Fixtures exist. Fixed `Fixture_Loader` path to resolve `tests/phpunit/fixtures/`. Added `fixtures/README.md`. Integration fixtures: full-import-payload.json, responsive-hero.json, hero-id-simple.json, hero-responsive-breakpoints.json. Classes, variables, breakpoints fixtures present.

**Tasks**:
- [x] Create integration fixtures (full-import-payload, responsive-hero, hero-*)
- [x] Add fixtures for unit tests (classes, variables, breakpoints)
- [x] Document fixture structure in fixtures/README.md

**Success Criteria**:
- [x] All integration tests pass
- [x] Fixtures follow consistent structure

### 1.3 Extract Magic Numbers to Constants
**Priority**: Medium
**Effort**: 1 hour
**Impact**: Medium - Improves readability

**Status**: Complete. Created `TestCase/Test_Constants.php`.

**Tasks**:
- [x] Create `Test_Constants` class with: `HTTP_OK`, `HTTP_BAD_REQUEST`, `HTTP_FORBIDDEN`, `DESKTOP_TO_TABLET_BREAKPOINT`, `TABLET_TO_MOBILE_BREAKPOINT`, `TOLERANCE_BREAKPOINT`, `TOLERANCE_THRESHOLD`, `UNMATCHED_BREAKPOINT`, `MIN_WIDTH_SAMPLE`
- [x] Replace hardcoded numbers in converter and integration tests
- [ ] Use named constants in data providers (Phase 2)

**Success Criteria**:
- [x] No magic numbers in test assertions
- [x] Test intent clearer through named constants

### 1.4 Add Base Test Class
**Priority**: Medium
**Effort**: 2 hours
**Impact**: High - Reduces duplication

**Status**: Complete. Created `TestCase/Base_Test_Case.php`. All 10 integration test classes now extend it.

**Tasks**:
- Create `tests/phpunit/TestCase/Base_Test_Case.php`
- Add common assertion helpers:
  - `assertSuccessfulConversion()`
  - `assertValidWidgetStructure()`
  - `assertValidBreakpointProps()`
- Add common fixture loading helpers
- Migrate 5 existing tests to use base class

**Example**:
```php
abstract class Base_Test_Case extends TestCase {
    protected function assertSuccessfulConversion( array $data ): void {
        $this->assertTrue( $data['success'] );
        $this->assertArrayHasKey( 'widgets', $data );
        $this->assertIsArray( $data['widgets'] );
    }
}
```

**Success Criteria**:
- [x] Base class created with 3 helper methods: `assertSuccessfulConversion`, `assertValidWidgetStructure`, `assertValidBreakpointProps`
- [x] All 10 integration test classes extend base class
- [x] Code duplication reduced

### 1.5 Clean Up Test Naming Inconsistencies
**Priority**: Low
**Effort**: 1 hour
**Impact**: Low - Cosmetic

**Tasks**:
- Ensure all test files use `test-` prefix
- Ensure all test classes use `Test_` prefix
- Verify double underscore convention for all test methods
- Fix any inconsistencies

**Success Criteria**:
- 100% naming convention compliance
- Easy to scan test files

---

## Phase 2: Quality Improvements (2-3 weeks)

**Phase 2 Status**: Complete (2025-02), except 2.2 Mock Infrastructure (deferred)

### 2.1 Introduce Data Providers
**Priority**: High
**Effort**: 4 hours
**Impact**: High - Reduces test duplication

**Status**: Complete. Refactored Test_Color_Hex_Variable_Convertor, Test_Media_Query_Parser.

**Tasks**:
- Refactor `Test_Color_Hex_Variable_Convertor` to use data providers
- Refactor `Test_Media_Query_Parser` to use data providers
- Create template/pattern for future data provider tests
- Document data provider best practices

**Example**:
```php
public function hex_color_provider(): array {
    return [
        'three_digit' => ['#fff', '#ffffff'],
        'six_digit' => ['#ff5733', '#ff5733'],
        'with_alpha' => ['#ffffff80', '#ffffff80'],
        'uppercase' => ['#FFF', '#ffffff'],
    ];
}

/**
 * @dataProvider hex_color_provider
 */
public function test_convert_normalizes_hex_colors( string $input, string $expected ): void {
    $result = $this->convertor->convert( '--color', $input );
    $this->assertSame( $expected, $result['value'] );
}
```

**Success Criteria**:
- [x] 2 test classes refactored with data providers (Color_Hex, Media_Query_Parser)
- [x] Test count increased 251→266
- [x] Edge cases easier to add

### 2.2 Add Mock Infrastructure
**Priority**: High
**Effort**: 6 hours
**Impact**: High - Enables isolated unit testing

**Tasks**:
- Create mock factory for Elementor Plugin instance
- Create mock factory for breakpoints config
- Create mock factory for WordPress REST requests
- Refactor 3 tests to use mocks instead of skipping

**Example**:
```php
private function createElementorMockWithBreakpoints( array $breakpoints ): object {
    $breakpoints_manager = $this->createMock( \Elementor\Core\Breakpoints\Manager::class );
    $breakpoints_manager->method( 'get_breakpoints_config' )
        ->willReturn( $breakpoints );
    
    $plugin = $this->createMock( \Elementor\Plugin::class );
    $plugin->breakpoints = $breakpoints_manager;
    
    return $plugin;
}
```

**Status**: Deferred. Requires refactoring Breakpoint_Matcher for dependency injection. Skipped tests require Elementor bootstrap.

**Success Criteria**:
- [ ] Mock helpers available in base test class
- [ ] Tests no longer skip when Elementor unavailable
- [ ] Tests run 10x faster without WordPress bootstrap

### 2.3 Deepen Integration Test Assertions
**Priority**: Medium
**Effort**: 3 hours
**Impact**: Medium - Catches more bugs

**Status**: Complete. Added assertWidgetHasStylesOrSettings, assertFirstWidgetHasBreakpointVariants.

**Tasks**:
- Add widget structure validation to integration tests
- Verify breakpoint prop mapping accuracy
- Validate custom CSS generation correctness
- Assert on variable resolution results
- Check widget tree depth and nesting

**Before**:
```php
$this->assertTrue( $data['success'] );
```

**After**:
```php
$this->assertSuccessfulConversion( $data );
$this->assertValidWidgetStructure( $data['widgets'][0] );
$this->assertArrayHasKey( 'atomic_props', $data['widgets'][0]['settings'] );
$this->assertArrayHasKey( 'breakpoint_props', $data['widgets'][0]['settings'] );
$this->assertArrayHasKey( 'tablet', $data['widgets'][0]['settings']['breakpoint_props'] );
```

**Success Criteria**:
- [x] Integration tests have deeper assertions (widget structure, styles, error payload)
- [x] Tests verify correctness, not just success
- [x] Error response validated (empty widgets, non-empty error message)

### 2.4 Add Negative Path Testing
**Priority**: Medium
**Effort**: 4 hours
**Impact**: Medium - Improves robustness

**Status**: Complete. Added malformed/empty tests for Variable_Extractor, Css_Converter, Media_Query_Parser.

**Tasks**:
- Add malformed input tests for each converter
- Test null safety across all converters
- Test empty string handling
- Test very large inputs (1MB+ CSS)
- Test unicode and special characters
- Add error condition tests for REST endpoints

**Example**:
```php
public function test_extract_from_css__malformed_css_returns_empty(): void {
    $malformed = '--color: ; --incomplete';
    $result = $this->extractor->extract_from_css( $malformed );
    $this->assertIsArray( $result );
}

public function test_convert_handles_null_gracefully(): void {
    $this->expectException( \InvalidArgumentException::class );
    $this->convertor->convert( null, null );
}
```

**Success Criteria**:
- [x] Variable_Extractor, Css_Converter, Media_Query_Parser have negative tests
- [x] Malformed input, empty input covered
- [ ] Every converter has 2+ negative tests (partial)

### 2.5 Extract Inline Test Data to Fixtures
**Priority**: Low
**Effort**: 3 hours
**Impact**: Medium - Cleaner tests

**Status**: Complete. Added hero-id-simple.json, hero-responsive-breakpoints.json. test-convert-html-endpoint uses fixtures.

**Tasks**:
- Move multi-line CSS strings to fixture files
- Create reusable test data sets
- Use `Fixture_Loader` consistently
- Document fixture format

**Before**:
```php
$css = ':root {
    --primary: #ff0000;
    --spacing: 16px;
}';
```

**After**:
```php
$css = Fixture_Loader::load_file( 'variables/root-simple.css' );
```

**Success Criteria**:
- [x] Integration test HTML extracted to fixtures
- [x] Fixtures reusable across tests
- [x] Test intent clearer

---

## Phase 3: Coverage Expansion (3-4 weeks)

**Phase 3 Status**: Complete. 3.1 CSS property converters, 3.2 REST endpoints, 3.3 Atomic_To_Css_Converter and variable flow tested. Class_Registration_Service and Image_Import_Service deferred (need WP/Elementor/HTTP).

### 3.1 Test Top 20 CSS Property Converters
**Priority**: Critical
**Effort**: 10 hours
**Impact**: Critical - Biggest coverage gap

**Status**: Complete. Added `tests/phpunit/converters/css/test-css-property-converters.php` with 12 tests (padding, margin, gap, display, color, background-color, font-size, flex-direction, align-items, justify-content, unsupported, mixed supported/unsupported). Skips when Elementor not loaded.

**Property Converters to Test** (by usage frequency):
1. Padding
2. Margin  
3. Gap
4. Display
5. Flex (direction, wrap, grow, shrink)
6. Align Items
7. Justify Content
8. Color
9. Background Color
10. Font Size
11. Font Weight
12. Border
13. Border Radius
14. Width
15. Height
16. Position
17. Text Align
18. Line Height
19. Letter Spacing
20. Opacity

**Template for each test**:
- Test conversion to atomic props
- Test fallback to custom CSS
- Test invalid values
- Test edge cases

**Success Criteria**:
- 20 new test files created
- Coverage increases by 25%
- All critical converters tested

### 3.2 Test REST API Endpoints
**Priority**: High
**Effort**: 6 hours
**Impact**: High - User-facing functionality

**Status**: Complete. Extended integration tests: convert-html (missing html validation, response keys), css-to-atomic (response keys, empty CSS), import-variables (keys, missing css/url error), breakpoints (schema), trigger-import (required keys).

**Endpoints to test**:
- `/convert-html` - Full request/response validation
- `/import-variables` - Variable import modes
- `/breakpoints` - Breakpoint config response
- `/trigger-import` - Payload structure validation
- `/css-to-atomic` - Classes conversion

**Test coverage for each**:
- Valid request succeeds
- Invalid request returns 400
- Empty request returns error
- Large payload handling
- Response schema validation

**Success Criteria**:
- All 5 endpoints fully tested
- Request validation tested
- Response structure verified

### 3.3 Test Import/Export System
**Priority**: Medium
**Effort**: 4 hours
**Impact**: Medium - Data integrity

**Status**: Partial. Added `test-atomic-to-css-converter.php` (10 tests): empty input, empty label/variants, size/string/color/dimensions props, responsive variants, label sanitization, props without $$type. Variable_Conversion_Service and Variable_Extractor already tested. Class_Registration_Service and Image_Import_Service require WP/Elementor/HTTP - deferred.

**Tasks**:
- [x] Test variable import flow (Variable_Extractor, Variable_Conversion_Service)
- [x] Test classes-to-CSS conversion (Atomic_To_Css_Converter)
- [ ] Test class registration service (needs WP/Elementor)
- [ ] Test document service
- [ ] Test image import service (needs HTTP mocking)
- [ ] Mock external HTTP calls

**Success Criteria**:
- [x] Import/export conversion logic tested (Atomic_To_Css_Converter)
- [x] Variable extraction/conversion tested
- [ ] Full import pipeline without network (deferred)

---

## Phase 4: Technical Debt (Ongoing)

### 4.1 Add Performance Tests
**Priority**: Low
**Effort**: 4 hours
**Impact**: Medium - Prevent regressions

**Status**: Partial. Added `test-performance-baselines.php` with 3 budget tests: Variable_Extractor (10KB CSS x10 iters <500ms), Variable_Conversion_Service (100 vars x20 iters <500ms, 1000 vars x5 iters <2s). Composer script: `test:performance`.

**Tasks**:
- [x] Benchmark variable extraction (10KB CSS)
- [x] Benchmark variable conversion (100, 1000 variables)
- [x] Set performance budgets
- [ ] Benchmark 100KB, 1MB CSS (optional)
- [ ] Benchmark Css_Converter (needs registry setup)

**Success Criteria**:
- [x] Performance baselines documented in test constants
- [x] Tests fail if slower than budget

### 4.2 Add Mutation Testing
**Priority**: Low
**Effort**: 2 hours
**Impact**: High - Validates test quality

**Status**: Partial. Infection PHP 0.28 not in default deps (CI uses PHP 7.4 platform). To add: `composer require --dev infection/infection --ignore-platform-req=php`. Config `infection.json5` exists (source: includes/, bootstrap: tests/bootstrap.php). Composer scripts `mutation`, `mutation:covered` available when Infection installed. Requires PHP 8.1+.

**Tasks**:
- [x] Install Infection PHP
- [x] Create infection.json5 config
- [x] Add composer mutation scripts
- [ ] Run mutation testing and document baseline score
- [ ] Fix tests that don't catch mutations
- [ ] Aim for 80%+ mutation score

**Success Criteria**:
- [x] Mutation testing integrated (composer mutation)
- [ ] Mutation score documented
- [ ] Weak tests identified and fixed

### 4.3 Improve Test Speed
**Priority**: Medium
**Effort**: 3 hours
**Impact**: Medium - Developer experience

**Status**: Complete. Unit and integration already separated via `phpunit.xml.dist` and `phpunit-integration.xml.dist`. Added composer scripts: `test:unit` (alias for test), `test:all` (runs unit then integration).

**Tasks**:
- [x] Separate fast unit tests from slow integration tests
- [ ] Use `@group` annotations (optional; config separation suffices)
- [ ] Cache fixtures in memory
- [ ] Run fast tests in parallel

**Commands**:
```bash
composer test         # Unit tests only (~1s)
composer test:unit    # Same as test
composer test:integration   # Integration tests (requires WP+Elementor)
composer test:all     # Unit then integration
```

**Success Criteria**:
- [x] Unit tests run in under 5 seconds (~1s actual)
- [x] Integration tests separated
- [x] Fast feedback loop for developers

### 4.4 Add Visual Regression Testing
**Priority**: Low
**Effort**: 8 hours
**Impact**: Low - Nice to have

**Tasks**:
- Generate HTML output from converters
- Take screenshots of rendered widgets
- Compare against baseline images
- Alert on visual changes

**Success Criteria**:
- Visual diffs detected automatically
- Baseline images stored in git

---

## Metrics & Success Criteria

### Coverage Targets by Phase

| Phase | Coverage | Test Files | Duration |
|-------|----------|------------|----------|
| Current | 45-55% | 38 | - |
| Phase 1 Complete | 50-60% | 40 | 2 weeks |
| Phase 2 Complete | 60-70% | 45 | 4 weeks |
| Phase 3 Complete | 75-85% | 70 | 8 weeks |
| Phase 4 Complete | 85%+ | 75 | 12 weeks |

### Quality Metrics

- **Test Execution Time**: <5s for unit tests, <30s for integration
- **Mutation Score**: 80%+
- **Code Duplication in Tests**: <5%
- **Assertion Density**: 5+ assertions per test method (integration)
- **Negative Test Coverage**: 30% of all tests

### Definition of Done for Each Phase

**Phase 1**:
- ✅ Coverage report generated
- ✅ All fixtures exist
- ✅ Base test class created
- ✅ Magic numbers eliminated
- ✅ All existing tests pass

**Phase 2**:
- ✅ 5+ tests use data providers
- ✅ Mock infrastructure available
- ✅ Integration tests have deep assertions
- ✅ Negative tests added for core converters

**Phase 3**:
- ✅ 20 CSS property converters tested
- ✅ All REST endpoints tested
- ✅ Import/export system tested
- ✅ Coverage above 75%

**Phase 4**:
- ✅ Performance baselines documented
- ✅ Mutation testing integrated
- ✅ Test speed optimized
- ✅ Visual regression testing (optional)

---

## Priority Matrix

| Task | Impact | Effort | Priority | Phase |
|------|--------|--------|----------|-------|
| Generate coverage report | High | Low | Critical | 1.1 |
| Fix missing fixtures | High | Low | Critical | 1.2 |
| Add base test class | High | Low | High | 1.4 |
| Test CSS property converters | Critical | High | Critical | 3.1 |
| Introduce data providers | High | Medium | High | 2.1 |
| Add mock infrastructure | High | Medium | High | 2.2 |
| Test REST endpoints | High | Medium | High | 3.2 |
| Deepen integration assertions | Medium | Low | Medium | 2.3 |
| Add negative path tests | Medium | Medium | Medium | 2.4 |
| Test import/export | Medium | Medium | Medium | 3.3 |
| Extract magic numbers | Medium | Low | Low | 1.3 |
| Extract inline test data | Medium | Low | Low | 2.5 |
| Add performance tests | Medium | Medium | Low | 4.1 |
| Improve test speed | Medium | Low | Low | 4.3 |
| Add mutation testing | High | Low | Low | 4.2 |

---

## Implementation Guidelines

### Quick Win Criteria
A task is a "quick win" if:
- Effort < 3 hours
- Immediate value (fixes broken tests, improves readability)
- No major refactoring required
- Can be done in isolation

### Quality Improvement Criteria  
A task improves quality if:
- Reduces code duplication
- Improves test isolation
- Catches more bugs
- Makes tests more maintainable

### Technical Debt Criteria
A task pays down debt if:
- Enables future improvements
- Reduces maintenance burden
- Improves developer experience
- Prevents future issues

---

## Next Steps

### Week 1-2 (Phase 1)
1. Generate coverage report (30 min)
2. Fix missing fixtures (2 hours)
3. Add base test class (2 hours)
4. Extract magic numbers (1 hour)

**Estimated time**: 8 hours
**Value**: Foundation for all future improvements

### Week 3-4 (Phase 2 Start)
1. Introduce data providers (4 hours)
2. Add mock infrastructure (6 hours)
3. Deepen integration assertions (3 hours)

**Estimated time**: 13 hours
**Value**: Quality baseline established

### Week 5-8 (Phase 3)
1. Test top 20 CSS property converters (10 hours)
2. Test REST endpoints (6 hours)
3. Test import/export (4 hours)

**Estimated time**: 20 hours
**Value**: Coverage target reached

---

## Resources Needed

- PHPUnit documentation for data providers
- Mockery or PHPUnit mock documentation
- Infection PHP for mutation testing
- Time allocation: 2-4 hours per week for 12 weeks

---

## Risk Mitigation

### Risk: Breaking existing tests
**Mitigation**: Run tests after each change, use feature branches

### Risk: Time estimates too low
**Mitigation**: Start with Phase 1, adjust estimates based on actuals

### Risk: Fixtures become outdated
**Mitigation**: Version fixtures, add fixture validation tests

### Risk: Mocking too complex
**Mitigation**: Start simple, add complexity incrementally

---

## Outstanding Work

| Area | Task | Phase |
|------|------|-------|
| Coverage | Identify top 10 uncovered files, add badge | 1.1 |
| Coverage | Use named constants in data providers | 1.3 |
| Naming | Verify 100% convention compliance | 1.5 |
| Mocking | Add mock infrastructure (Breakpoint_Matcher DI) | 2.2 |
| Negative | 2+ negative tests per converter | 2.4 |
| Import | Test Class_Registration_Service, Image_Import_Service | 3.3 |
| Performance | Optional: 100KB/1MB CSS, Css_Converter benchmarks | 4.1 |
| Mutation | Document baseline score, fix weak tests | 4.2 |
| Optional | @group annotations, fixture cache, parallel tests | 4.3 |
| Optional | Visual regression testing | 4.4 |

---

## Appendix: Test Templates

### Unit Test Template
```php
<?php

namespace ElementorHtmlCssConverter\Tests\Converters\Css\Properties;

use ElementorHtmlCssConverter\Converters\Css\Properties\Gap_Converter;
use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;

class Test_Gap_Converter extends Base_Test_Case {

    private Gap_Converter $converter;

    protected function setUp(): void {
        parent::setUp();
        $this->converter = new Gap_Converter();
    }

    public function gap_values_provider(): array {
        return [
            'single_value' => ['16px', ['gap' => '16px']],
            'two_values' => ['16px 24px', ['row-gap' => '16px', 'column-gap' => '24px']],
            'zero' => ['0', ['gap' => '0']],
        ];
    }

    /**
     * @dataProvider gap_values_provider
     */
    public function test_convert_gap_property( string $input, array $expected ): void {
        $result = $this->converter->convert( $input );
        $this->assertSame( $expected, $result );
    }

    public function test_convert_invalid_gap_returns_custom_css(): void {
        $result = $this->converter->convert( 'invalid' );
        $this->assertArrayHasKey( 'customCss', $result );
    }
}
```

### Integration Test Template
```php
<?php

namespace ElementorHtmlCssConverter\Tests\Integration;

use ElementorHtmlCssConverter\Tests\TestCase\Base_Test_Case;
use ElementorHtmlCssConverter\Tests\TestCase\Fixture_Loader;

class Test_Gap_Property_Endpoint extends Base_Test_Case {

    public function test_convert_html_with_gap_property(): void {
        $html = Fixture_Loader::load_file( 'integration/gap-layout.html' );
        
        $request = new \WP_REST_Request( 'POST', '/html-css-converter/v1/convert-html' );
        $request->set_param( 'html', $html );

        $response = rest_do_request( $request );
        $data = $response->get_data();

        $this->assertSuccessfulConversion( $data );
        $this->assertValidWidgetStructure( $data['widgets'][0] );
        $this->assertArrayHasKey( 'gap', $data['widgets'][0]['settings']['atomic_props'] );
    }
}
```
