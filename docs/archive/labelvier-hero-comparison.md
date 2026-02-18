# Label Vier Hero Comparison: `.page-header__hero` (First Item)

**Source:** https://labelvier.nl/  
**Import target:** http://elementor.local/elementor-469/  
**Scope:** First `.page-header__hero` element only

---

## Selector Mapping

| Original selector | New selector |
| ----------------- | ------------ |
| `#scraped-0` | `.e-2886637c-c51d31f` |
| `#scraped-0-1` | `.e-792e2948-7401fdb` |
| `#scraped-0-2` | `.e-75527b84-aa2f331` |
| `#scraped-0-3` | (arrow – hidden on tablet) |
| `#scraped-0-5` | `.e-6213a5c2-fa2dc42` |
| `#scraped-0-6` | (nested in content) |
| `#scraped-0-8` | `.e-1f47dec2-fd42c4f` |
| `#scraped-0-10` | `.e-51cce49d-4a1c5f8` |
| `#scraped-0-12` | `.e-46c258fc-882c80d` |
| `#scraped-0-13` | `.e-f4e63cc-7b9e81e` |
| `#scraped-0-14` | `.e-61840d03-8fa8222` |

---

## Element: `#scraped-0` → `.e-2886637c-c51d31f` (Root Hero Container)

| CSS property | Expected | Received | |
| ------------ | -------- | -------- | --- |
| box-sizing | inherit | - | ❌ |
| -webkit-text-size-adjust | 100% | - | ❌ |

---

## Element: `#scraped-0-2` → `.e-75527b84-aa2f331` (Hero Image)

| CSS property | Expected | Received | |
| ------------ | -------- | -------- | --- |
| -o-object-fit | cover | - | ❌ |
| object-position | 46% 39% | - | ❌ |
| width | 100% | 3840px | ❌ |
| height | 100% | 2560px | ❌ |
| aspect-ratio | auto 3840 / 2560 | - | ⚠️ |

> **Note:** The `width: 3840px` and `height: 2560px` issue comes from img HTML attributes overriding the intended `width: 100%` / `height: 100%` from `.u-image-background-container img`. This has been fixed in the scraper (attributesStyle is now checked after stylesheet rules).

---

## Element: `#scraped-0-8` → `.e-1f47dec2-fd42c4f` (H2 Heading)

| CSS property | Expected | Received | |
| ------------ | -------- | -------- | --- |
| font-size | max(1.2121212121vw,calc(1.9 * var(...))) | max(3.83838vw, 40px) | ⚠️ |

---

## Element: `#scraped-0-13` → `.e-f4e63cc-7b9e81e` (Primary Button)

| CSS property | Expected | Received | |
| ------------ | -------- | -------- | --- |
| background-image | linear-gradient(transparent 0,#f7cdd5 0,#f7cdd5 100%,transparent 100%) | linear-gradient(rgb(247,205,213) 100%, rgba(0,0,0,0) 100%) | ⚠️ |
| background-size | 0 100% | auto | ❌ |
| background-repeat | no-repeat | repeat | ❌ |

---

## Legend

| Symbol | Meaning |
| ------ | ------- |
| ❌ | Mismatch: Missing or different value |
| ⚠️ | Partial: Format differs or semantic equivalence unclear |
