# Labelvier Style Comparison 3

## Element: `#scraped-0` → `.e-7536bb3e-7db78c1` (Outer Container)


| Property                 | Expected                 | Received                 | Match |
| ------------------------ | ------------------------ | ------------------------ | ----- |
| box-sizing               | inherit                  | inherit                  | ✅     |
| background               | #fff                     | -                        | ❌     |
| background-color         | -                        | #fff                     | ⚠️    |
| font-size                | 62.5%                    | max(1.2121212121vw,calc(1.9 * var(--original-rem-base-bct2a3qn2q))) | ❌     |
| font-family              | "Hind Guntur",sans-serif | "Hind Guntur",sans-serif | ✅     |
| font-weight              | 400                      | 400                      | ✅     |
| color                    | #000                     | #000                     | ✅     |
| line-height              | 1.15                     | 1.15em                   | ⚠️    |
| -webkit-text-size-adjust | 100%                     | 100%                     | ✅     |
| padding-top              | max(1.5151515152vw,3rem) | -                        | ❌     |
| padding-bottom           | max(3.0303030303vw,6rem) | -                        | ❌     |
| padding-block-start      | -                        | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q))) | ⚠️    |
| padding-block-end        | -                        | max(3.0303030303vw,calc(6 * var(--original-rem-base-bct2a3qn2q))) | ⚠️    |


## Element: `#scraped-0-1` → `.e-dc64c6fa-f619bd8` (Inner Container)


| Property             | Expected      | Received      | Match |
| -------------------- | ------------- | ------------- | ----- |
| display              | flex          | flex          | ✅     |
| align-items          | center        | center        | ✅     |
| justify-content      | space-between | space-between | ✅     |
| width                | 100%          | 100%          | ✅     |
| max-width            | 70vw          | 70vw          | ✅     |
| margin               | 0 auto        | -             | ❌     |
| margin-block-start   | -             | 0px           | ⚠️    |
| margin-block-end     | -             | 0px           | ⚠️    |
| margin-inline-start  | -             | auto          | ⚠️    |
| margin-inline-end    | -             | auto          | ⚠️    |
| padding              | 0             | -             | ❌     |
| padding-block-start  | -             | 0px           | ⚠️    |
| padding-block-end    | -             | 0px           | ⚠️    |
| padding-inline-start | -             | 0px           | ⚠️    |
| padding-inline-end   | -             | 0px           | ⚠️    |


## Element: `#scraped-0-2` → `.e-32f30fdb-cffd8cc` (Heading)


| Property            | Expected                                              | Received                    | Match |
| ------------------- | ----------------------------------------------------- | --------------------------- | ----- |
| font-family         | "Hind Guntur",sans-serif                              | "Hind Guntur",sans-serif    | ✅     |
| font-weight         | 200                                                   | 200                         | ✅     |
| line-height         | 1.1                                                   | 1.1em                       | ⚠️    |
| font-size           | max(2.7272727273vw,3.75rem)                           | max(2.7272727273vw,calc(3.75 * var(--original-rem-base-bct2a3qn2q))) | ⚠️    |
| color               | inherit                                               | inherit                     | ✅     |
| margin              | max(.2525252525vw,.5rem) max(1.5151515152vw,3rem) 0 0 | -                           | ❌     |
| margin-block-start  | -                                                     | max(.2525252525vw,calc(.5 * var(--original-rem-base-bct2a3qn2q))) | ⚠️    |
| margin-block-end    | -                                                     | 0px                         | ⚠️    |
| margin-inline-start | -                                                     | 0px                         | ⚠️    |
| margin-inline-end   | -                                                     | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q))) | ⚠️    |


## Element: `#scraped-0-3` → (Strong tag inside heading)


| Property    | Expected | Received | Match |
| ----------- | -------- | -------- | ----- |
| font-weight | 700      | -        | ❌     |


## Element: `#scraped-0-4` → `.e-fd91beb0-cdc2e5a` (Button/Link)


| Property                   | Expected                                                                   | Received                                                             | Match |
| -------------------------- | -------------------------------------------------------------------------- | -------------------------------------------------------------------- | ----- |
| background-color           | #e2655e                                                                    | #e2655e                                                              | ✅     |
| color                      | #fff                                                                       | #fff                                                                 | ✅     |
| white-space                | nowrap                                                                     | nowrap                                                               | ✅     |
| position                   | relative                                                                   | relative                                                             | ✅     |
| font-family                | "Hind Guntur",sans-serif                                                   | "Hind Guntur",sans-serif                                             | ✅     |
| font-weight                | 700                                                                        | 700                                                                  | ✅     |
| font-size                  | max(.9090909091vw,1.8rem)                                                  | max(.9090909091vw,calc(1.8 * var(--original-rem-base-bct2a3qn2q)))  | ⚠️    |
| display                    | block                                                                      | block                                                                | ✅     |
| text-transform             | lowercase                                                                  | lowercase                                                            | ✅     |
| align-items                | center                                                                     | center                                                               | ✅     |
| justify-content            | center                                                                     | center                                                               | ✅     |
| cursor                     | pointer                                                                    | pointer                                                              | ✅     |
| border                     | 0                                                                          | -                                                                    | ❌     |
| border-width               | -                                                                          | 0px                                                                  | ⚠️    |
| border-style               | -                                                                          | none                                                                 | ⚠️    |
| line-height                | 1                                                                          | 1em                                                                  | ⚠️    |
| text-decoration            | none                                                                       | none                                                                 | ✅     |
| background-image           | linear-gradient(transparent 0,#f7cdd5 0,#f7cdd5 100%,transparent 100%)     | linear-gradient(180deg, #f7cdd5 100%,rgba(0,0,0,0) 100%)            | ❌     |
| background-size            | 0 100%                                                                     | auto auto                                                            | ❌     |
| background-repeat          | no-repeat                                                                  | repeat                                                               | ❌     |
| transition                 | .5s ease                                                                   | .5s ease                                                             | ✅     |
| border-radius              | 0                                                                          | -                                                                    | ❌     |
| border-top-width           | 0px                                                                        | -                                                                    | ❌     |
| border-right-width         | 0px                                                                        | -                                                                    | ❌     |
| border-bottom-width         | 0px                                                                        | -                                                                    | ❌     |
| border-left-width           | 0px                                                                        | -                                                                    | ❌     |
| border-top-style           | initial                                                                    | -                                                                    | ❌     |
| border-right-style         | initial                                                                    | -                                                                    | ❌     |
| border-bottom-style        | initial                                                                    | -                                                                    | ❌     |
| border-left-style          | initial                                                                    | -                                                                    | ❌     |
| border-top-color           | initial                                                                    | -                                                                    | ❌     |
| border-right-color         | initial                                                                    | -                                                                    | ❌     |
| border-left-color          | initial                                                                    | -                                                                    | ❌     |
| border-top-left-radius     | 0px                                                                        | -                                                                    | ❌     |
| border-top-right-radius    | 0px                                                                        | -                                                                    | ❌     |
| border-bottom-right-radius | 0px                                                                        | -                                                                    | ❌     |
| border-bottom-left-radius  | 0px                                                                        | -                                                                    | ❌     |
| border-start-start-radius  | -                                                                          | 0px                                                                  | ⚠️    |
| border-start-end-radius    | -                                                                          | 0px                                                                  | ⚠️    |
| border-end-start-radius    | -                                                                          | 0px                                                                  | ⚠️    |
| border-end-end-radius      | -                                                                          | 0px                                                                  | ⚠️    |
| width                      | -moz-fit-content                                                           | fit-content                                                          | ⚠️    |
| min-height                 | max(2.6767676768vw,5.3rem)                                                 | max(2.6767676768vw,calc(5.3 * var(--original-rem-base-bct2a3qn2q))) | ⚠️    |
| padding                    | max(.9595959596vw,1.9rem) max(1.5151515152vw,3rem) max(.5050505051vw,1rem) | -                                                                    | ❌     |
| padding-block-start        | -                                                                          | max(.9595959596vw,calc(1.9 * var(--original-rem-base-bct2a3qn2q)))  | ⚠️    |
| padding-block-end          | -                                                                          | max(.5050505051vw,calc(1 * var(--original-rem-base-bct2a3qn2q)))    | ⚠️    |
| padding-inline-start       | -                                                                          | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q)))  | ⚠️    |
| padding-inline-end         | -                                                                          | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q)))  | ⚠️    |
| background-attachment      | -                                                                          | scroll                                                               | ⚠️    |
| background-position        | -                                                                          | 0% 0%                                                                | ⚠️    |


## Legend

- ✅ Match: Property exists in both and values match (or are equivalent)
- ⚠️ Partial: Property exists but format/value differs (e.g., logical properties vs physical properties, unit differences, variable usage)
- ❌ Missing: Property exists in expected but not in received, or vice versa
