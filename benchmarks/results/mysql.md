# Benchmark results: mysql

Median latency over 10 random origins, in milliseconds. Rows examined is approximate.

| Rows | Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | v1 rows examined | v2 rows examined | geohash rows examined |
|---|---|---|---|---|---|---|---|---|
| 10,000 | uniform | nearest(take: 1) | 12.6 | 1.3 | 1.6 | 10,003 | 2 | 10 |
| 10,000 | uniform | nearest(take: 10) | 11.0 | 14.4 | 19.2 | 10,003 | 44 | 406 |
| 10,000 | uniform | nearby(1 km) | 9.9 | 1.3 | 1.7 | 10,003 | 2 | 10 |
| 10,000 | uniform | nearby(5 km) | 9.5 | 1.5 | 1.8 | 10,003 | 2 | 11 |
| 10,000 | uniform | nearby(50 km) | 9.8 | 1.2 | 1.3 | 10,003 | 2 | 16 |
| 10,000 | dense | nearest(take: 1) | 11.6 | 1.3 | 1.6 | 10,003 | 2 | 18 |
| 10,000 | dense | nearest(take: 10) | 10.6 | 6.4 | 9.1 | 10,003 | 20 | 636 |
| 10,000 | dense | nearby(1 km) | 8.9 | 1.2 | 1.5 | 10,003 | 2 | 18 |
| 10,000 | dense | nearby(5 km) | 9.0 | 1.9 | 2.4 | 10,003 | 6 | 300 |
| 10,000 | dense | nearby(50 km) | 10.1 | 8.0 | 19.0 | 10,003 | 432 | 10,003 |
| 100,000 | uniform | nearest(take: 1) | 100.4 | 1.3 | 1.9 | 100,003 | 2 | 10 |
| 100,000 | uniform | nearest(take: 10) | 100.8 | 12.3 | 22.6 | 100,003 | 32 | 199 |
| 100,000 | uniform | nearby(1 km) | 88.1 | 1.3 | 1.7 | 100,003 | 2 | 10 |
| 100,000 | uniform | nearby(5 km) | 88.0 | 1.4 | 1.5 | 100,003 | 2 | 11 |
| 100,000 | uniform | nearby(50 km) | 87.4 | 1.9 | 2.9 | 100,003 | 2 | 49 |
| 100,000 | dense | nearest(take: 1) | 93.9 | 1.4 | 2.1 | 100,003 | 5 | 114 |
| 100,000 | dense | nearest(take: 10) | 94.6 | 5.3 | 8.9 | 100,003 | 54 | 342 |
| 100,000 | dense | nearby(1 km) | 84.9 | 1.4 | 2.2 | 100,003 | 5 | 114 |
| 100,000 | dense | nearby(5 km) | 83.7 | 3.5 | 11.3 | 100,003 | 58 | 1,702 |
| 100,000 | dense | nearby(50 km) | 96.4 | 119.4 | 204.5 | 100,003 | 100,003 | 100,003 |
| 1,000,000 | uniform | nearest(take: 1) | 1008.3 | 1.4 | 1.7 | 1,000,003 | 2 | 10 |
| 1,000,000 | uniform | nearest(take: 10) | 1009.8 | 14.1 | 14.8 | 1,000,003 | 34 | 714 |
| 1,000,000 | uniform | nearby(1 km) | 874.4 | 1.3 | 1.6 | 1,000,003 | 2 | 10 |
| 1,000,000 | uniform | nearby(5 km) | 876.5 | 1.6 | 1.7 | 1,000,003 | 2 | 18 |
| 1,000,000 | uniform | nearby(50 km) | 878.2 | 3.7 | 5.0 | 1,000,003 | 8 | 320 |
| 1,000,000 | dense | nearest(take: 1) | 1131.7 | 2.5 | 6.5 | 1,000,003 | 26 | 1,013 |
| 1,000,000 | dense | nearest(take: 10) | 947.6 | 3.0 | 8.2 | 1,000,003 | 26 | 1,013 |
| 1,000,000 | dense | nearby(1 km) | 833.2 | 3.2 | 8.8 | 1,000,003 | 26 | 1,013 |
| 1,000,000 | dense | nearby(5 km) | 874.0 | 19.6 | 206.5 | 1,000,003 | 460 | 32,020 |
| 1,000,000 | dense | nearby(50 km) | 954.5 | 1263.1 | 2079.1 | 1,000,003 | 1,000,003 | 1,000,003 |
