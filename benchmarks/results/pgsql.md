# Benchmark results: pgsql

Median latency over 10 random origins, in milliseconds. Rows examined is approximate.

| Rows | Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | v1 rows examined | v2 rows examined | geohash rows examined |
|---|---|---|---|---|---|---|---|---|
| 10,000 | uniform | nearest(take: 1) | 12.9 | 1.7 | 2.5 | 10,000 | 1 | 2 |
| 10,000 | uniform | nearest(take: 10) | 12.3 | 14.4 | 19.6 | 10,000 | 83 | 522 |
| 10,000 | uniform | nearby(1 km) | 11.7 | 1.5 | 2.0 | 10,000 | 1 | 2 |
| 10,000 | uniform | nearby(5 km) | 10.5 | 1.7 | 2.1 | 10,000 | 1 | 2 |
| 10,000 | uniform | nearby(50 km) | 10.4 | 1.6 | 1.9 | 10,000 | 2 | 16 |
| 10,000 | dense | nearest(take: 1) | 11.2 | 1.6 | 2.2 | 10,000 | 1 | 12 |
| 10,000 | dense | nearest(take: 10) | 11.2 | 9.3 | 10.8 | 10,000 | 138 | 1,932 |
| 10,000 | dense | nearby(1 km) | 9.6 | 1.5 | 1.7 | 10,000 | 1 | 12 |
| 10,000 | dense | nearby(5 km) | 9.5 | 1.6 | 2.9 | 10,000 | 8 | 636 |
| 10,000 | dense | nearby(50 km) | 11.3 | 10.7 | 20.6 | 10,000 | 1,164 | 11,102 |
| 100,000 | uniform | nearest(take: 1) | 114.1 | 1.8 | 2.3 | 100,000 | 1 | 2 |
| 100,000 | uniform | nearest(take: 10) | 112.3 | 16.7 | 20.1 | 100,000 | 124 | 2,862 |
| 100,000 | uniform | nearby(1 km) | 95.3 | 1.6 | 2.0 | 100,000 | 1 | 2 |
| 100,000 | uniform | nearby(5 km) | 96.4 | 1.6 | 2.2 | 100,000 | 1 | 2 |
| 100,000 | uniform | nearby(50 km) | 96.4 | 1.8 | 2.1 | 100,000 | 2 | 80 |
| 100,000 | dense | nearest(take: 1) | 160.1 | 1.8 | 2.4 | 100,000 | 12 | 194 |
| 100,000 | dense | nearest(take: 10) | 105.6 | 5.8 | 7.8 | 100,000 | 96 | 6,660 |
| 100,000 | dense | nearby(1 km) | 95.0 | 1.6 | 2.3 | 100,000 | 12 | 194 |
| 100,000 | dense | nearby(5 km) | 91.8 | 4.4 | 10.4 | 100,000 | 108 | 6,272 |
| 100,000 | dense | nearby(50 km) | 98.3 | 94.0 | 266.0 | 100,000 | 11,224 | 137,072 |
| 1,000,000 | uniform | nearest(take: 1) | 768.5 | 2.1 | 2.8 | 999,999 | 1 | 2 |
| 1,000,000 | uniform | nearest(take: 10) | 925.9 | 45.5 | 48.6 | 999,999 | 53 | 1,690 |
| 1,000,000 | uniform | nearby(1 km) | 742.3 | 7.3 | 5.2 | 999,999 | 1 | 2 |
| 1,000,000 | uniform | nearby(5 km) | 784.1 | 3.1 | 4.1 | 999,999 | 1 | 36 |
| 1,000,000 | uniform | nearby(50 km) | 818.1 | 10.8 | 9.8 | 999,999 | 25 | 806 |
| 1,000,000 | dense | nearest(take: 1) | 633.5 | 5.3 | 21.7 | 999,999 | 34 | 1,908 |
| 1,000,000 | dense | nearest(take: 10) | 620.1 | 3.5 | 7.3 | 999,999 | 34 | 1,908 |
| 1,000,000 | dense | nearby(1 km) | 523.8 | 6.3 | 8.2 | 999,999 | 34 | 1,908 |
| 1,000,000 | dense | nearby(5 km) | 567.8 | 28.3 | 120.9 | 999,999 | 1,062 | 34,184 |
| 1,000,000 | dense | nearby(50 km) | 676.7 | 1347.5 | 2111.9 | 1,000,002 | 72,304 | 801,881 |
