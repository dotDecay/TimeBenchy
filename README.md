# Benchmark Class TimeBenchy

Small benchmark tool for measuring execution times.

## Usage

```php
$timeBenchy = new TimeBenchy();
$timeBenchy->mark('Start');
// [... some code here ...]
$timeBenchy->mark('After function X');
// [... some code here ...]
$timeBenchy->mark('After Database call');
// [... some code here ...]
$timeBenchy->mark('End');
$timeBenchy->printStats();
```