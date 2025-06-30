# open-telemetry

Open Telemetry integration

![Continuous Integration](https://github.com/mammatusphp/open-telemetry/workflows/Continuous%20Integration/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/mammatus/open-telemetry/v/stable.png)](https://packagist.org/packages/mammatus/open-telemetry)
[![Total Downloads](https://poser.pugx.org/mammatus/open-telemetry/downloads.png)](https://packagist.org/packages/mammatus/open-telemetry/stats)
[![Type Coverage](https://shepherd.dev/github/mammatusphp/open-telemetry/coverage.svg)](https://shepherd.dev/github/mammatusphp/open-telemetry)
[![License](https://poser.pugx.org/mammatus/open-telemetry/license.png)](https://packagist.org/packages/mammatus/open-telemetry)

# Installation

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `^`.

```
composer require mammatus/open-telemetry
```

# Todo

- [X] Port initial implementation over from private project
- [ ] Optimize configuration, switch from simple to batch handle
- [ ] Switch to non-blocking PSR handler, current one blocks with `time_nanosleep`
- [ ] Align with https://opentelemetry.io/docs/languages/php/instrumentation/#initialize-the-sdk for SDK initialization

# License

The MIT License (MIT)

Copyright (c) 2025 Cees-Jan Kiewiet

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
