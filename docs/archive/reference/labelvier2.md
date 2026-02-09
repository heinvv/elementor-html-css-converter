# Labelvier Style Comparison 2

## Element: `#scraped-0` → `.e-fa028b41-990f30f` (Outer Container)


| Property                 | Expected                 | Received                 | Match |
| ------------------------ | ------------------------ | ------------------------ | ----- |
| box-sizing               | inherit                  | -                        | [X]     |
| background               | #fff                     | -                        | [X]     |
| background-color         | -                        | #fff                     | [!]    |
| font-size                | 62.5%                    | 62.5%                    | [OK]     |
| font-family              | "Hind Guntur",sans-serif | "Hind Guntur",sans-serif | [OK]     |
| font-weight              | 400                      | 400                      | [OK]     |
| color                    | #000                     | #000                     | [OK]     |
| line-height              | 1.15                     | 1.15em                   | [!]    |
| -webkit-text-size-adjust | 100%                     | -                        | [X]     |
| padding-top              | max(1.5151515152vw,3rem) | -                        | [X]     |
| padding-bottom           | max(3.0303030303vw,6rem) | -                        | [X]     |
| padding-block-start      | -                        | max(1.5151515152vw,3rem) | [!]    |
| padding-block-end        | -                        | max(3.0303030303vw,6rem) | [!]    |


## Element: `#scraped-0-1` → `.e-314cd267-7ab1700` (Inner Container)


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


## Element: `#scraped-0-2` → `.e-90605148-ea0466f` (Heading)


| Property            | Expected                                              | Received                    | Match |
| ------------------- | ----------------------------------------------------- | --------------------------- | ----- |
| font-family         | "Hind Guntur",sans-serif                              | "Hind Guntur",sans-serif    | [OK]     |
| font-weight         | 200                                                   | 200                         | [OK]     |
| line-height         | 1.1                                                   | 1.1em                       | [!]    |
| font-size           | max(2.7272727273vw,3.75rem)                           | max(2.7272727273vw,3.75rem) | [OK]     |
| color               | inherit                                               | inherit                     | [OK]     |
| margin              | max(.2525252525vw,.5rem) max(1.5151515152vw,3rem) 0 0 | -                           | [X]     |
| margin-block-start  | -                                                     | max(.2525252525vw,.5rem)    | [!]    |
| margin-block-end    | -                                                     | 0px                         | [!]    |
| margin-inline-start | -                                                     | 0px                         | [!]    |
| margin-inline-end   | -                                                     | max(1.5151515152vw,3rem)    | [!]    |


## Element: `#scraped-0-3` → (Strong tag inside heading)


| Property    | Expected | Received | Match |
| ----------- | -------- | -------- | ----- |
| font-weight | 700      | -        | [X]     |


## Element: `#scraped-0-4` → `.e-38ae3cce-96f7079` (Button/Link)


| Property                   | Expected                                                                   | Received                                                             | Match |
| -------------------------- | -------------------------------------------------------------------------- | -------------------------------------------------------------------- | ----- |
| background-color           | #e2655e                                                                    | #e2655e                                                              | [OK]     |
| color                      | #fff                                                                       | #fff                                                                 | [OK]     |
| white-space                | nowrap                                                                     | -                                                                    | [X]     |
| position                   | relative                                                                   | relative                                                             | [OK]     |
| font-family                | "Hind Guntur",sans-serif                                                   | "Hind Guntur",sans-serif                                             | [OK]     |
| font-weight                | 700                                                                        | 700                                                                  | [OK]     |
| font-size                  | max(.9090909091vw,1.8rem)                                                  | max(.9090909091vw,1.8rem)                                            | [OK]     |
| display                    | block                                                                      | block                                                                | [OK]     |
| text-transform             | lowercase                                                                  | lowercase                                                            | [OK]     |
| align-items                | center                                                                     | center                                                               | [OK]     |
| justify-content            | center                                                                     | center                                                               | [OK]     |
| cursor                     | pointer                                                                    | -                                                                    | [X]     |
| border                     | 0                                                                          | -                                                                    | [X]     |
| border-width               | -                                                                          | 0px                                                                  | [!]    |
| border-style               | -                                                                          | none                                                                 | [!]    |
| line-height                | 1                                                                          | 1em                                                                  | [!]    |
| text-decoration            | none                                                                       | none                                                                 | [OK]     |
| background-image           | linear-gradient(transparent 0,#f7cdd5 0,#f7cdd5 100%,transparent 100%)     | linear-gradient(180deg, #f7cdd5 100%,rgba(0,0,0,0) 100%),url("none") | [X]     |
| background-size            | 0 100%                                                                     | auto auto,0px 100%                                                   | [X]     |
| background-repeat          | no-repeat                                                                  | repeat,no-repeat                                                     | [X]     |
| transition                 | .5s ease                                                                   | -                                                                    | [X]     |
| border-radius              | 0                                                                          | -                                                                    | [X]     |
| border-top-width           | 0px                                                                        | -                                                                    | [X]     |
| border-right-width         | 0px                                                                        | -                                                                    | [X]     |
| border-bottom-width        | 0px                                                                        | -                                                                    | [X]     |
| border-left-width          | 0px                                                                        | -                                                                    | [X]     |
| border-top-style           | initial                                                                    | -                                                                    | [X]     |
| border-right-style         | initial                                                                    | -                                                                    | [X]     |
| border-bottom-style        | initial                                                                    | -                                                                    | [X]     |
| border-left-style          | initial                                                                    | -                                                                    | [X]     |
| border-top-color           | initial                                                                    | -                                                                    | [X]     |
| border-right-color         | initial                                                                    | -                                                                    | [X]     |
| border-bottom-color        | initial                                                                    | -                                                                    | [X]     |
| border-left-color          | initial                                                                    | -                                                                    | [X]     |
| border-top-left-radius     | 0px                                                                        | -                                                                    | [X]     |
| border-top-right-radius    | 0px                                                                        | -                                                                    | [X]     |
| border-bottom-right-radius | 0px                                                                        | -                                                                    | [X]     |
| border-bottom-left-radius  | 0px                                                                        | -                                                                    | [X]     |
| border-end-start-radius    | -                                                                          | 0px                                                                  | [!]    |
| width                      | -moz-fit-content                                                           | -                                                                    | [X]     |
| min-height                 | max(2.6767676768vw,5.3rem)                                                 | max(2.6767676768vw,5.3rem)                                           | [OK]     |
| padding                    | max(.9595959596vw,1.9rem) max(1.5151515152vw,3rem) max(.5050505051vw,1rem) | -                                                                    | [X]     |
| padding-block-start        | -                                                                          | max(.9595959596vw,1.9rem)                                            | [!]    |
| padding-block-end          | -                                                                          | max(.5050505051vw,1rem)                                              | [!]    |
| padding-inline-start       | -                                                                          | max(1.5151515152vw,3rem)                                             | [!]    |
| padding-inline-end         | -                                                                          | max(1.5151515152vw,3rem)                                             | [!]    |
| background-attachment      | -                                                                          | scroll                                                               | [!]    |
| background-position        | -                                                                          | 0% 0%                                                                | [!]    |


## Legend

- [OK] Match: Property exists in both and values match (or are equivalent)
- [!] Partial: Property exists but format/value differs (e.g., logical properties vs physical properties, unit differences)
- [X] Missing: Property exists in expected but not in received, or vice versa

