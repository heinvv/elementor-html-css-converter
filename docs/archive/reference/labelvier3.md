# Labelvier Style Comparison 3

## Element: `#scraped-0` → `.e-7536bb3e-7db78c1` (Outer Container)


| Property                 | Expected                 | Received                 | Match |
| ------------------------ | ------------------------ | ------------------------ | ----- |
| box-sizing               | inherit                  | inherit                  | [OK]     |
| background               | #fff                     | -                        | [X]     |
| background-color         | -                        | #fff                     | [!]    |
| font-size                | 62.5%                    | max(1.2121212121vw,calc(1.9 * var(--original-rem-base-bct2a3qn2q))) | [X]     |
| font-family              | "Hind Guntur",sans-serif | "Hind Guntur",sans-serif | [OK]     |
| font-weight              | 400                      | 400                      | [OK]     |
| color                    | #000                     | #000                     | [OK]     |
| line-height              | 1.15                     | 1.15em                   | [!]    |
| -webkit-text-size-adjust | 100%                     | 100%                     | [OK]     |
| padding-top              | max(1.5151515152vw,3rem) | -                        | [X]     |
| padding-bottom           | max(3.0303030303vw,6rem) | -                        | [X]     |
| padding-block-start      | -                        | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q))) | [!]    |
| padding-block-end        | -                        | max(3.0303030303vw,calc(6 * var(--original-rem-base-bct2a3qn2q))) | [!]    |


## Element: `#scraped-0-1` → `.e-dc64c6fa-f619bd8` (Inner Container)


| Property             | Expected      | Received      | Match |
| -------------------- | ------------- | ------------- | ----- |
| display              | flex          | flex          | [OK]     |
| align-items          | center        | center        | [OK]     |
| justify-content      | space-between | space-between | [OK]     |
| width                | 100%          | 100%          | [OK]     |
| max-width            | 70vw          | 70vw          | [OK]     |
| margin               | 0 auto        | -             | [X]     |
| margin-block-start   | -             | 0px           | [!]    |
| margin-block-end     | -             | 0px           | [!]    |
| margin-inline-start  | -             | auto          | [!]    |
| margin-inline-end    | -             | auto          | [!]    |
| padding              | 0             | -             | [X]     |
| padding-block-start  | -             | 0px           | [!]    |
| padding-block-end    | -             | 0px           | [!]    |
| padding-inline-start | -             | 0px           | [!]    |
| padding-inline-end   | -             | 0px           | [!]    |


## Element: `#scraped-0-2` → `.e-32f30fdb-cffd8cc` (Heading)


| Property            | Expected                                              | Received                    | Match |
| ------------------- | ----------------------------------------------------- | --------------------------- | ----- |
| font-family         | "Hind Guntur",sans-serif                              | "Hind Guntur",sans-serif    | [OK]     |
| font-weight         | 200                                                   | 200                         | [OK]     |
| line-height         | 1.1                                                   | 1.1em                       | [!]    |
| font-size           | max(2.7272727273vw,3.75rem)                           | max(2.7272727273vw,calc(3.75 * var(--original-rem-base-bct2a3qn2q))) | [!]    |
| color               | inherit                                               | inherit                     | [OK]     |
| margin              | max(.2525252525vw,.5rem) max(1.5151515152vw,3rem) 0 0 | -                           | [X]     |
| margin-block-start  | -                                                     | max(.2525252525vw,calc(.5 * var(--original-rem-base-bct2a3qn2q))) | [!]    |
| margin-block-end    | -                                                     | 0px                         | [!]    |
| margin-inline-start | -                                                     | 0px                         | [!]    |
| margin-inline-end   | -                                                     | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q))) | [!]    |


## Element: `#scraped-0-3` → (Strong tag inside heading)


| Property    | Expected | Received | Match |
| ----------- | -------- | -------- | ----- |
| font-weight | 700      | -        | [X]     |


## Element: `#scraped-0-4` → `.e-fd91beb0-cdc2e5a` (Button/Link)


| Property                   | Expected                                                                   | Received                                                             | Match |
| -------------------------- | -------------------------------------------------------------------------- | -------------------------------------------------------------------- | ----- |
| background-color           | #e2655e                                                                    | #e2655e                                                              | [OK]     |
| color                      | #fff                                                                       | #fff                                                                 | [OK]     |
| white-space                | nowrap                                                                     | nowrap                                                               | [OK]     |
| position                   | relative                                                                   | relative                                                             | [OK]     |
| font-family                | "Hind Guntur",sans-serif                                                   | "Hind Guntur",sans-serif                                             | [OK]     |
| font-weight                | 700                                                                        | 700                                                                  | [OK]     |
| font-size                  | max(.9090909091vw,1.8rem)                                                  | max(.9090909091vw,calc(1.8 * var(--original-rem-base-bct2a3qn2q)))  | [!]    |
| display                    | block                                                                      | block                                                                | [OK]     |
| text-transform             | lowercase                                                                  | lowercase                                                            | [OK]     |
| align-items                | center                                                                     | center                                                               | [OK]     |
| justify-content            | center                                                                     | center                                                               | [OK]     |
| cursor                     | pointer                                                                    | pointer                                                              | [OK]     |
| border                     | 0                                                                          | -                                                                    | [X]     |
| border-width               | -                                                                          | 0px                                                                  | [!]    |
| border-style               | -                                                                          | none                                                                 | [!]    |
| line-height                | 1                                                                          | 1em                                                                  | [!]    |
| text-decoration            | none                                                                       | none                                                                 | [OK]     |
| background-image           | linear-gradient(transparent 0,#f7cdd5 0,#f7cdd5 100%,transparent 100%)     | linear-gradient(180deg, #f7cdd5 100%,rgba(0,0,0,0) 100%)            | [X]     |
| background-size            | 0 100%                                                                     | auto auto                                                            | [X]     |
| background-repeat          | no-repeat                                                                  | repeat                                                               | [X]     |
| transition                 | .5s ease                                                                   | .5s ease                                                             | [OK]     |
| border-radius              | 0                                                                          | -                                                                    | [X]     |
| border-top-width           | 0px                                                                        | -                                                                    | [X]     |
| border-right-width         | 0px                                                                        | -                                                                    | [X]     |
| border-bottom-width         | 0px                                                                        | -                                                                    | [X]     |
| border-left-width           | 0px                                                                        | -                                                                    | [X]     |
| border-top-style           | initial                                                                    | -                                                                    | [X]     |
| border-right-style         | initial                                                                    | -                                                                    | [X]     |
| border-bottom-style        | initial                                                                    | -                                                                    | [X]     |
| border-left-style          | initial                                                                    | -                                                                    | [X]     |
| border-top-color           | initial                                                                    | -                                                                    | [X]     |
| border-right-color         | initial                                                                    | -                                                                    | [X]     |
| border-left-color          | initial                                                                    | -                                                                    | [X]     |
| border-top-left-radius     | 0px                                                                        | -                                                                    | [X]     |
| border-top-right-radius    | 0px                                                                        | -                                                                    | [X]     |
| border-bottom-right-radius | 0px                                                                        | -                                                                    | [X]     |
| border-bottom-left-radius  | 0px                                                                        | -                                                                    | [X]     |
| border-start-start-radius  | -                                                                          | 0px                                                                  | [!]    |
| border-start-end-radius    | -                                                                          | 0px                                                                  | [!]    |
| border-end-start-radius    | -                                                                          | 0px                                                                  | [!]    |
| border-end-end-radius      | -                                                                          | 0px                                                                  | [!]    |
| width                      | -moz-fit-content                                                           | fit-content                                                          | [!]    |
| min-height                 | max(2.6767676768vw,5.3rem)                                                 | max(2.6767676768vw,calc(5.3 * var(--original-rem-base-bct2a3qn2q))) | [!]    |
| padding                    | max(.9595959596vw,1.9rem) max(1.5151515152vw,3rem) max(.5050505051vw,1rem) | -                                                                    | [X]     |
| padding-block-start        | -                                                                          | max(.9595959596vw,calc(1.9 * var(--original-rem-base-bct2a3qn2q)))  | [!]    |
| padding-block-end          | -                                                                          | max(.5050505051vw,calc(1 * var(--original-rem-base-bct2a3qn2q)))    | [!]    |
| padding-inline-start       | -                                                                          | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q)))  | [!]    |
| padding-inline-end         | -                                                                          | max(1.5151515152vw,calc(3 * var(--original-rem-base-bct2a3qn2q)))  | [!]    |
| background-attachment      | -                                                                          | scroll                                                               | [!]    |
| background-position        | -                                                                          | 0% 0%                                                                | [!]    |


## Legend

- [OK] Match: Property exists in both and values match (or are equivalent)
- [!] Partial: Property exists but format/value differs (e.g., logical properties vs physical properties, unit differences, variable usage)
- [X] Missing: Property exists in expected but not in received, or vice versa
