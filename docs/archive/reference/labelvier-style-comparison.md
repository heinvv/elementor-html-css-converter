# Labelvier Style Comparison

## Element: `#scraped-0` → `.e-4c1b82df-026cf5f` (Outer Container)


| Property                 | Expected | Received | Match |
| ------------------------ | -------- | -------- | ----- |
| box-sizing               | inherit  | -        | ❌     |
| -webkit-text-size-adjust | 100%     | -        | ❌     |


## Element: `#scraped-0-1` → `.e-ba47af43-be8a056` (Inner Container)


| Property | Expected | Received | Match |
| -------- | -------- | -------- | ----- |
| margin   | 0 auto   | -        | ❌     |


## Element: `#scraped-0-2` → `.e-9b1ac3b8-9e006be` (Heading)


| Property  | Expected                     | Received | Match |
| --------- | ---------------------------- | -------- | ----- |
| font-size | max(2.7272727273vw, 3.75rem) | -        | ❌     |


## Element: `#scraped-0-4` → `.e-480fa0d0-9854031` (Button/Link)


| Property                   | Expected                                                                  | Received                                                 | Match |
| -------------------------- | ------------------------------------------------------------------------- | -------------------------------------------------------- | ----- |
| white-space                | nowrap                                                                    | -                                                        | ❌     |
| font-size                  | max(.9090909091vw, 1.8rem)                                                | -                                                        | ❌     |
| cursor                     | pointer                                                                   | -                                                        | ❌     |
| border                     | 0                                                                         | -                                                        | ❌     |
| background-image           | linear-gradient(transparent 0, #f7cdd5 0, #f7cdd5 100%, transparent 100%) | linear-gradient(180deg, #f7cdd5 100%,rgba(0,0,0,0) 100%) | ❌     |
| background-size            | 0 100%                                                                    | auto auto                                                | ❌     |
| background-repeat          | no-repeat                                                                 | repeat                                                   | ❌     |
| transition                 | .5s ease                                                                  | -                                                        | ❌     |
| border-radius              | 0                                                                         | -                                                        | ❌     |
| border-top-width           | 0px                                                                       | -                                                        | ❌     |
| border-right-width         | 0px                                                                       | -                                                        | ❌     |
| border-bottom-width        | 0px                                                                       | -                                                        | ❌     |
| border-left-width          | 0px                                                                       | -                                                        | ❌     |
| border-top-style           | initial                                                                   | -                                                        | ❌     |
| border-right-style         | initial                                                                   | -                                                        | ❌     |
| border-bottom-style        | initial                                                                   | -                                                        | ❌     |
| border-left-style          | initial                                                                   | -                                                        | ❌     |
| border-top-color           | initial                                                                   | -                                                        | ❌     |
| border-right-color         | initial                                                                   | -                                                        | ❌     |
| border-bottom-color        | initial                                                                   | -                                                        | ❌     |
| border-left-color          | initial                                                                   | -                                                        | ❌     |
| border-top-left-radius     | 0px                                                                       | -                                                        | ❌     |
| border-top-right-radius    | 0px                                                                       | -                                                        | ❌     |
| border-bottom-right-radius | 0px                                                                       | -                                                        | ❌     |
| border-bottom-left-radius  | 0px                                                                       | -                                                        | ❌     |
| border-end-start-radius    | -                                                                         | 0px                                                      | ⚠️    |
| width                      | -moz-fit-content                                                          | -                                                        | ❌     |
| min-height                 | max(2.6767676768vw, 5.3rem)                                               | -                                                        | ❌     |
| padding-block-start        | -                                                                         | max(.9595959596vw,1.9rem)                                | ⚠️    |
| padding-block-end          | -                                                                         | max(.5050505051vw,1rem)                                  | ⚠️    |
| padding-inline-start       | -                                                                         | max(1.5151515152vw,3rem)                                 | ⚠️    |
| padding-inline-end         | -                                                                         | max(1.5151515152vw,3rem)                                 | ⚠️    |
| background-attachment      | -                                                                         | scroll                                                   | ⚠️    |
| background-position        | -                                                                         | 0% 0%                                                    | ⚠️    |


## Legend

- ✅ Match: Property exists in both and values match (or are equivalent)
- ⚠️ Partial: Property exists but format/value differs (e.g., logical properties vs physical properties, unit differences)
- ❌ Missing: Property exists in expected but not in received, or vice versa

