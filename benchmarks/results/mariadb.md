# Benchmark results: mariadb

Median latency over 10 random origins, in milliseconds. Rows examined is approximate.

| Rows | Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | v1 rows examined | v2 rows examined | geohash rows examined |
|---|---|---|---|---|---|---|---|---|
| 10,000 | uniform | nearest(take: 1) | 11.1 | 1.1 | 1.3 | 10,002 | 3 | 11 |
| 10,000 | uniform | nearest(take: 10) | 10.6 | 13.4 | 13.0 | 10,011 | 69 | 401 |
| 10,000 | uniform | nearby(1 km) | 9.8 | 1.2 | 1.4 | 10,002 | 3 | 11 |
| 10,000 | uniform | nearby(5 km) | 9.0 | 1.3 | 1.4 | 10,002 | 3 | 11 |
| 10,000 | uniform | nearby(50 km) | 9.2 | 1.4 | 1.9 | 10,002 | 3 | 14 |
| 10,000 | dense | nearest(take: 1) | 9.4 | 1.1 | 1.3 | 10,002 | 3 | 23 |
| 10,000 | dense | nearest(take: 10) | 9.7 | 5.6 | 8.1 | 10,011 | 48 | 457 |
| 10,000 | dense | nearby(1 km) | 8.1 | 1.2 | 1.7 | 10,002 | 3 | 23 |
| 10,000 | dense | nearby(5 km) | 8.3 | 1.2 | 2.0 | 10,011 | 21 | 207 |
| 10,000 | dense | nearby(50 km) | 9.9 | 11.1 | 20.2 | 10,340 | 10,340 | 10,340 |
| 100,000 | uniform | nearest(take: 1) | 99.3 | 1.3 | 1.6 | 100,002 | 3 | 11 |
| 100,000 | uniform | nearest(take: 10) | 98.6 | 10.8 | 14.6 | 100,011 | 57 | 1,089 |
| 100,000 | uniform | nearby(1 km) | 84.1 | 1.3 | 1.5 | 100,002 | 3 | 11 |
| 100,000 | uniform | nearby(5 km) | 85.1 | 1.3 | 1.6 | 100,002 | 3 | 12 |
| 100,000 | uniform | nearby(50 km) | 83.9 | 1.6 | 1.6 | 100,004 | 7 | 30 |
| 100,000 | dense | nearest(take: 1) | 89.7 | 1.4 | 1.8 | 100,002 | 7 | 108 |
| 100,000 | dense | nearest(take: 10) | 92.3 | 4.8 | 7.1 | 100,011 | 32 | 229 |
| 100,000 | dense | nearby(1 km) | 74.0 | 1.2 | 1.7 | 100,006 | 11 | 112 |
| 100,000 | dense | nearby(5 km) | 76.5 | 3.6 | 12.6 | 100,043 | 85 | 3,294 |
| 100,000 | dense | nearby(50 km) | 92.7 | 128.0 | 242.4 | 104,640 | 104,640 | 104,640 |
| 1,000,000 | uniform | nearest(take: 1) | 1060.3 | 3.6 | 2.0 | 1,000,002 | 3 | 11 |
| 1,000,000 | uniform | nearest(take: 10) | 1043.6 | 16.3 | 15.1 | 1,000,011 | 55 | 432 |
| 1,000,000 | uniform | nearby(1 km) | 880.3 | 2.3 | 1.9 | 1,000,002 | 3 | 11 |
| 1,000,000 | uniform | nearby(5 km) | 963.6 | 2.0 | 1.6 | 1,000,002 | 3 | 16 |
| 1,000,000 | uniform | nearby(50 km) | 996.7 | 3.3 | 4.2 | 1,000,012 | 23 | 184 |
| 1,000,000 | dense | nearest(take: 1) | 1015.4 | 3.4 | 6.0 | 1,000,002 | 19 | 1,004 |
| 1,000,000 | dense | nearest(take: 10) | 923.0 | 3.0 | 6.4 | 1,000,011 | 28 | 1,013 |
| 1,000,000 | dense | nearby(1 km) | 764.7 | 2.8 | 7.3 | 1,000,018 | 35 | 1,020 |
| 1,000,000 | dense | nearby(5 km) | 747.8 | 18.1 | 128.4 | 1,000,428 | 855 | 31,935 |
| 1,000,000 | dense | nearby(50 km) | 960.4 | 1268.8 | 2207.9 | 1,043,962 | 1,043,962 | 1,043,962 |
