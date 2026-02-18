# Elementor Pro: Conditional Frontend Handler Loading

## Problem

When Elementor Pro is activated — even on a page containing **only** v4/atomic widgets (`e-div-block`, `e-heading`) — all of these scripts load unconditionally:

| Script | Handle |
|--------|--------|
| `webpack-pro.runtime.js` | `elementor-pro-webpack-runtime` |
| `frontend.js` (Pro) | `elementor-pro-frontend` |
| `elements-handlers.js` (Pro) | `pro-elements-handlers` |
| `frontend-modules.js` (Core) | `elementor-frontend-modules` |
| `webpack.runtime.js` (Core) | `elementor-webpack-runtime` |
| `frontend.js` (Core) | `elementor-frontend` |

Elementor core already solved this for its own scripts — on a v4-only page, `elementor-frontend` and its dependency chain are **not loaded**. Activating Pro undoes this because `pro-elements-handlers` declares `elementor-frontend` as a dependency, pulling the entire core v3 chain back in.

**Priority:** Conditionally load Pro frontend scripts so the core v3 chain is not forced onto v4-only pages.

---

## How Elementor Core Solves This

Three pillars make conditional loading work in core.

### Pillar 1: Per-element script declarations

Every element declares its script needs via two methods on `Element_Base`:

- `get_script_depends()` — widget-specific scripts (e.g. `swiper`, `imagesloaded`)
- `get_global_scripts()` — framework-level scripts (e.g. `elementor-frontend`)

**v3 elements** (`Element_Base`) return `['elementor-frontend']` from `get_global_scripts()`.

**v4/atomic elements** (`Atomic_Element_Base`) return `[]` from `get_global_scripts()` and add `elementor-v2-widgets-frontend` via `get_script_depends()`.

Key files:

| File | Lines |
|------|-------|
| `elementor/includes/base/element-base.php` | 119–125 — default `get_global_scripts()` returns `['elementor-frontend']` |
| `elementor/modules/atomic-widgets/elements/base/atomic-element-base.php` | 36 — constructor adds `elementor-v2-widgets-frontend` to script depends |
| `elementor/modules/atomic-widgets/elements/base/atomic-element-base.php` | 67–69 — overrides `get_global_scripts()` to return `[]` |

### Pillar 2: Page assets collection and caching

The `Assets` iteration action walks every element on the page (on save or first render) and collects their declared scripts:

```php
// core/base/elements-iteration-actions/assets.php:34-36
$element_assets_depend = [
    'styles' => $element_data->get_style_depends(),
    'scripts' => array_merge(
        $element_data->get_script_depends(),
        $element_data->get_global_scripts()
    ),
];
```

The collected assets are stored as post meta `_elementor_page_assets` and reused on subsequent page loads without re-iteration.

Key file: `elementor/core/base/elements-iteration-actions/assets.php`

### Pillar 3: Conditional enqueuing via `enable_assets()`

Only scripts present in `_elementor_page_assets` are enqueued via `core/page-assets/loader.php`:

1. `wp_footer()` checks `_has_elementor_in_page`
2. Calls `enqueue_scripts()` → `enqueue_conditional_assets()`
3. `handle_page_assets()` reads `_elementor_page_assets` from post meta
4. Calls `assets_loader->enable_assets()` with only the scripts that page needs

**Result:** If a page has only atomic widgets, no element adds `elementor-frontend` to the page assets. Core `frontend.js`, `frontend-modules.js`, and all v3 handlers never load.

---

## How Elementor Pro Currently Loads

### What is already optimized: the JS handler layer

The Pro `elements-handlers.js` bundle imports 25 module frontends, but each module frontend is a **lightweight wrapper** that only registers handler callbacks via `attachHandler` with dynamic `import()`. The actual handler code is lazy-loaded only when the corresponding widget is present on the page.

Example — Carousel module frontend (entire file):

```js
// modules/carousel/assets/js/frontend/frontend.js
export default class extends elementorModules.Module {
    constructor() {
        super();
        elementorFrontend.elementsHandler.attachHandler( 'media-carousel',
            () => import( './handlers/media-carousel' ) );
        elementorFrontend.elementsHandler.attachHandler( 'testimonial-carousel',
            () => import( './handlers/testimonial-carousel' ) );
        elementorFrontend.elementsHandler.attachHandler( 'reviews',
            () => import( './handlers/testimonial-carousel' ) );
    }
}
```

This pattern is consistent across all 25 modules. The heavy handler code is code-split and only loaded when a matching widget exists in the DOM. **This JS-level optimization is already in place.**

### What is NOT optimized: the PHP script enqueuing

The problem is entirely at the PHP level. Pro enqueues its scripts **unconditionally** on every Elementor page.

**Hook wiring** (`plugin.php:414-417`):

```php
add_action( 'elementor/frontend/before_register_scripts', [ $this, 'register_frontend_scripts' ] );
add_action( 'elementor/frontend/before_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );
```

**Unconditional enqueuing** (`plugin.php:211-224`):

```php
public function enqueue_frontend_scripts() {
    wp_enqueue_script(
        'elementor-pro-frontend',
        ELEMENTOR_PRO_URL . 'assets/js/frontend' . $suffix . '.js',
        $this->get_frontend_depends(),
        ELEMENTOR_PRO_VERSION,
        true
    );
    wp_enqueue_script( 'pro-elements-handlers' );
}
```

No check for Pro widgets on the page. Both scripts are enqueued every time.

**Dependency chain forces core v3 scripts** (`plugin.php:285-293`):

```php
wp_register_script(
    'pro-elements-handlers',
    ELEMENTOR_PRO_URL . 'assets/js/elements-handlers' . $suffix . '.js',
    [ 'elementor-frontend' ],   // <-- forces core v3 to load
    ELEMENTOR_PRO_VERSION,
    true
);
```

`pro-elements-handlers` depends on `elementor-frontend`, which depends on `elementor-frontend-modules`, which depends on `elementor-webpack-runtime`. The entire core v3 chain is pulled in.

**Pro `frontend.js` also eagerly loads some modules** (`frontend.js:2-7,28-35`):

```js
import MotionFX from '../../../../modules/motion-fx/assets/js/frontend/frontend';
import Sticky from '../../../../modules/sticky/assets/js/frontend/frontend';
import CodeHighlight from '../../../../modules/code-highlight/assets/js/frontend/frontend';
import VideoPlaylist from '../../../../modules/video-playlist/assets/js/frontend/frontend';
import Payments from '../../../../modules/payments/assets/js/frontend/frontend';
import ProgressTracker from '../../../../modules/progress-tracker/assets/js/frontend/frontend';
```

These 6 modules are instantiated as "default handlers" regardless of what is on the page. They use `attachHandler` too (so their heavy code is lazy), but their wrapper registration runs unconditionally.

**Page-assets integration:** Pro only registers `e-sticky` with the assets loader. The main scripts `elementor-pro-frontend` and `pro-elements-handlers` are not part of the page-assets system.

---

## Gap Analysis

| Aspect | Elementor Core | Elementor Pro |
|--------|---------------|---------------|
| Script enqueuing | Conditional via `_elementor_page_assets` | Unconditional in `enqueue_frontend_scripts()` |
| Per-widget script declaration | `get_script_depends()` + `get_global_scripts()` | Not used for Pro framework scripts |
| Page-assets integration | Full (save + render iteration) | None (only `e-sticky` registered with loader) |
| JS handler loading | Lazy per handler | Lazy per handler (already optimized) |
| v4/atomic awareness | Atomic elements return `[]` for global scripts | No v4 consideration |
| Effect on v4-only pages | `elementor-frontend` not loaded | Forces `elementor-frontend` to load via dependency chain |

---

## What Needs to Change

### Change 1: Stop unconditionally enqueuing Pro scripts (highest priority)

`enqueue_frontend_scripts()` (line 211 of `plugin.php`) should **not** call `wp_enqueue_script()` directly for `elementor-pro-frontend` and `pro-elements-handlers`.

These scripts are already **registered** in `register_frontend_scripts()`. The unconditional `wp_enqueue_script()` calls need to be removed or gated so they only fire when a Pro v3 widget is on the page.

### Change 2: Pro widgets must declare Pro scripts as dependencies

Each Pro widget (or a Pro widget base class) needs to return Pro script handles from `get_script_depends()` so the `Assets` iteration action in core picks them up and stores them in `_elementor_page_assets`.

**Option A — Pro widget base class** adds `pro-elements-handlers` and `elementor-pro-frontend` to `get_script_depends()`.

**Option B — Per-module declaration:** Each Pro module's widgets add these handles in their own `get_script_depends()`.

Either way, core's `Assets` iteration then collects these handles and `enable_assets()` enqueues them only when present.

### Change 3: Decouple `pro-elements-handlers` from `elementor-frontend`

Currently `pro-elements-handlers` hard-depends on `elementor-frontend`. This dependency can be removed from the `wp_register_script` call because:

- When a Pro v3 widget is on the page, that widget inherits `elementor-frontend` from `get_global_scripts()` on `Element_Base` — it will already be in `_elementor_page_assets`.
- WordPress will enqueue `elementor-frontend` from the page-assets system, not from the dependency chain.
- Removing the hard dependency prevents the chain from being forced when Pro scripts are enqueued for any reason.

### Change 4: Invalidate cached `_elementor_page_assets`

Existing pages have cached `_elementor_page_assets` that does not include Pro script handles. When this change ships, the meta needs to be invalidated so it gets regenerated with Pro handles included.

This can be done by bumping a version constant that the `Assets` iteration checks, or by deleting the meta on Pro upgrade.

---

## Recommended Approach

### Phase 1 — Conditional enqueuing (primary goal)

1. Remove the unconditional `wp_enqueue_script()` calls for `elementor-pro-frontend` and `pro-elements-handlers` from `enqueue_frontend_scripts()`
2. Add `pro-elements-handlers` and `elementor-pro-frontend` to Pro widgets via a base class `get_script_depends()` override
3. Remove `elementor-frontend` from the `pro-elements-handlers` dependency array (let it come from the widget's `get_global_scripts()` via page assets)
4. Invalidate `_elementor_page_assets` on Pro update
5. Core's existing `Assets` iteration and `enable_assets()` handle the rest

**After Phase 1:** v4-only pages with Pro active no longer load any Pro or core v3 scripts.

### Phase 2 — v4 Pro widgets (future)

When Pro ships atomic/v4 widgets:

1. Extend `Atomic_Widget_Base` (or a Pro-specific atomic base)
2. Inherit `[]` from `get_global_scripts()` (from `Atomic_Element_Base`)
3. Declare only v4-compatible script handles in `get_script_depends()`
4. Do not pull in `elementor-frontend` or the v3 Pro handler bundle

---

## Key Files Reference

### Elementor Core

| File | Purpose |
|------|---------|
| `elementor/includes/base/element-base.php` (119–125) | Default `get_script_depends()` and `get_global_scripts()` |
| `elementor/modules/atomic-widgets/elements/base/atomic-element-base.php` (36, 67–69) | Atomic override: empty global scripts, adds v2 handler |
| `elementor/core/base/elements-iteration-actions/assets.php` | Collects per-element assets into `_elementor_page_assets` |
| `elementor/core/page-assets/loader.php` (108–124) | `enable_assets()` — conditional enqueue |
| `elementor/includes/frontend.php` (401–419, 617–638, 838–847) | Frontend enqueue chain and `handle_page_assets()` |

### Elementor Pro

| File | Purpose |
|------|---------|
| `elementor-pro/plugin.php` (211–224) | Unconditional `enqueue_frontend_scripts()` — the root cause |
| `elementor-pro/plugin.php` (274–320) | `register_frontend_scripts()` — registers handles |
| `elementor-pro/plugin.php` (411–417) | Hook wiring |
| `elementor-pro/assets/dev/js/frontend/elements-handlers.js` | Handler wrappers (25 modules, all use `attachHandler` + dynamic `import()`) |
| `elementor-pro/assets/dev/js/frontend/frontend.js` | Pro frontend entry — eagerly loads 6 default modules, instantiates all handlers |
