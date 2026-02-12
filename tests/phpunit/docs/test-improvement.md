# Test Improvement Plan

## Overview

Current state: ~38 test files, estimated 45-55% actual coverage
Goal: Reach 80% coverage with high-quality, maintainable tests

**Strategy**: Quick wins first, then systematic quality improvements and debt reduction.

---

## Phase 1: Quick Wins & Cleanup (1-2 weeks)

### 1.1 Generate Actual Coverage Report
**Priority**: Critical
**Effort**: 30 minutes
**Impact**: High - Need baseline metrics

**Tasks**:
- Run `composer run coverage` to generate HTML report
- Document actual coverage percentage per directory
- Identify top 10 uncovered files by usage frequency
- Add coverage badge to README

**Success Criteria**:
- Coverage report generated successfully
- Actual coverage percentage documented
- Coverage gaps visualized

### 1.2 Fix Missing Fixture Files
**Priority**: High
**Effort**: 2 hours
**Impact**: High - Tests currently fail

**Tasks**:
- Create `tests/phpunit/fixtures/integration/full-import-payload.json`
- Create `tests/phpunit/fixtures/integration/responsive-hero.json`
- Add 3-5 simple fixtures for unit tests
- Document fixture structure in README

**Files to create**:
```
tests/phpunit/fixtures/
├── integration/
│   ├── full-import-payload.json
│   └── responsive-hero.json
├── classes/
│   └── single-class-simple.json
├── variables/
│   └── root-simple.json
└── breakpoints/
    └── media-max-width-exact.json
```

**Success Criteria**:
- All integration tests pass without modification
- Fixtures follow consistent structure

### 1.3 Extract Magic Numbers to Constants
**Priority**: Medium
**Effort**: 1 hour
**Impact**: Medium - Improves readability

**Tasks**:
- Create `TestConstants` class with common values:
  - `DESKTOP_BREAKPOINT = 1024`
  - `TABLET_BREAKPOINT = 767`
  - `MOBILE_BREAKPOINT = 480`
  - `TOLERANCE_THRESHOLD = 200`
- Replace hardcoded numbers in all tests
- Use named constants in data providers

**Success Criteria**:
- No magic numbers in test assertions
- Test intent clearer through named constants

### 1.4 Add Base Test Class
**Priority**: Medium
**Effort**: 2 hours
**Impact**: High - Reduces duplication

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
- Base class created with 5+ helper methods
- At least 5 test classes extend base class
- Code duplication reduced by 30%

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

### 2.1 Introduce Data Providers
**Priority**: High
**Effort**: 4 hours
**Impact**: High - Reduces test duplication

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
- 5+ tests refactored to use data providers
- Test count increases while code decreases
- Edge cases easier to add

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

**Success Criteria**:
- Mock helpers available in base test class
- Tests no longer skip when Elementor unavailable
- Tests run 10x faster without WordPress bootstrap

### 2.3 Deepen Integration Test Assertions
**Priority**: Medium
**Effort**: 3 hours
**Impact**: Medium - Catches more bugs

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
- Integration tests have 5+ assertions per test
- Tests verify correctness, not just success
- Catch regression bugs before production

### 2.4 Add Negative Path Testing
**Priority**: Medium
**Effort**: 4 hours
**Impact**: Medium - Improves robustness

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
- Every converter has 2+ negative tests
- No uncaught exceptions in production
- Error messages are helpful

### 2.5 Extract Inline Test Data to Fixtures
**Priority**: Low
**Effort**: 3 hours
**Impact**: Medium - Cleaner tests

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
- Test methods under 20 lines
- Fixtures reusable across tests
- Test intent clearer

---

## Phase 3: Coverage Expansion (3-4 weeks)

### 3.1 Test Top 20 CSS Property Converters
**Priority**: Critical
**Effort**: 10 hours
**Impact**: Critical - Biggest coverage gap

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

**Tasks**:
- Test variable import service
- Test class registration service
- Test document service
- Test image import service
- Mock external HTTP calls

**Success Criteria**:
- Import/export tested without network calls
- Data transformation verified
- Error handling tested

---

## Phase 4: Technical Debt (Ongoing)

### 4.1 Add Performance Tests
**Priority**: Low
**Effort**: 4 hours
**Impact**: Medium - Prevent regressions

**Tasks**:
- Benchmark CSS parsing for 10KB, 100KB, 1MB files
- Benchmark variable conversion for 100, 1000 variables
- Set performance budgets
- Add performance regression tests

**Success Criteria**:
- Performance baselines documented
- Tests fail if 2x slower than baseline

### 4.2 Add Mutation Testing
**Priority**: Low
**Effort**: 2 hours
**Impact**: High - Validates test quality

**Tasks**:
- Install Infection PHP
- Run mutation testing on existing tests
- Fix tests that don't catch mutations
- Aim for 80%+ mutation score

**Success Criteria**:
- Mutation testing integrated in CI
- Mutation score documented
- Weak tests identified and fixed

### 4.3 Improve Test Speed
**Priority**: Medium
**Effort**: 3 hours
**Impact**: Medium - Developer experience

**Tasks**:
- Separate fast unit tests from slow integration tests
- Use `@group` annotations
- Cache fixtures in memory
- Run fast tests in parallel

**Commands**:
```bash
composer test:unit    # Fast unit tests only (<5s)
composer test:slow    # Integration tests (~30s)
composer test:all     # Everything
```

**Success Criteria**:
- Unit tests run in under 5 seconds
- Integration tests separated
- Fast feedback loop for developers

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
