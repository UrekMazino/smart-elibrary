# Common passwords

`common-passwords.txt` is the list `App\Rules\NotCommonPassword` checks new passwords against
(OWASP ASVS 5.0, requirement 6.2.4: at least the 3000 most common passwords that fit the password
policy). One password per line, lowercased; the check ignores case.

It holds 10,694 passwords of 12 or more characters (the policy's minimum; shorter ones are refused by
length anyway):

- the 10,000 most common from `xato-net-10-million-passwords-1000000.txt`, and
- every one from `100k-most-used-passwords-NCSC.txt` (UK National Cyber Security Centre),

both from [SecLists](https://github.com/danielmiessler/SecLists), `Passwords/Common-Credentials/`,
downloaded 2026-10-26. To rebuild it, take those files, keep lines of 12 to 128 characters, lowercase
them and drop duplicates, keeping the order (most common first).

## SecLists licence

MIT License

Copyright (c) 2018 Daniel Miessler

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
