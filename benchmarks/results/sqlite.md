# Benchmark results: sqlite

Median latency over 10 random origins, in milliseconds. Rows examined is approximate.

| Rows | Layout | Query | v1 brute force (ms) | v2 SQL driver (ms) | v2 geohash driver (ms) | v1 rows examined | v2 rows examined | geohash rows examined |
|---|---|---|---|---|---|---|---|---|
| 10,000 | uniform | nearest(take: 1) | 20.6 | 0.5 | 0.5 | n/a | n/a | n/a |
| 10,000 | uniform | nearest(take: 10) | 19.1 | 3.3 | 5.3 | n/a | n/a | n/a |
| 10,000 | uniform | nearby(1 km) | 19.4 | 0.3 | 0.5 | n/a | n/a | n/a |
| 10,000 | uniform | nearby(5 km) | 22.2 | 0.4 | 0.6 | n/a | n/a | n/a |
| 10,000 | uniform | nearby(50 km) | 24.4 | 0.3 | 0.4 | n/a | n/a | n/a |
| 10,000 | dense | nearest(take: 1) | 18.9 | 0.3 | 0.5 | n/a | n/a | n/a |
| 10,000 | dense | nearest(take: 10) | 18.4 | 1.6 | 3.5 | n/a | n/a | n/a |
| 10,000 | dense | nearby(1 km) | 18.6 | 0.3 | 0.4 | n/a | n/a | n/a |
| 10,000 | dense | nearby(5 km) | 18.5 | 0.4 | 1.5 | n/a | n/a | n/a |
| 10,000 | dense | nearby(50 km) | 19.7 | 10.1 | 26.1 | n/a | n/a | n/a |
| 100,000 | uniform | nearest(take: 1) | 188.4 | 0.3 | 0.4 | n/a | n/a | n/a |
| 100,000 | uniform | nearest(take: 10) | 187.7 | 3.0 | 8.6 | n/a | n/a | n/a |
| 100,000 | uniform | nearby(1 km) | 184.9 | 0.3 | 0.4 | n/a | n/a | n/a |
| 100,000 | uniform | nearby(5 km) | 184.6 | 0.3 | 0.4 | n/a | n/a | n/a |
| 100,000 | uniform | nearby(50 km) | 185.2 | 0.4 | 0.6 | n/a | n/a | n/a |
| 100,000 | dense | nearest(take: 1) | 187.1 | 0.4 | 0.9 | n/a | n/a | n/a |
| 100,000 | dense | nearest(take: 10) | 191.4 | 1.7 | 11.4 | n/a | n/a | n/a |
| 100,000 | dense | nearby(1 km) | 184.1 | 0.4 | 0.8 | n/a | n/a | n/a |
| 100,000 | dense | nearby(5 km) | 184.1 | 2.4 | 13.7 | n/a | n/a | n/a |
| 100,000 | dense | nearby(50 km) | 197.0 | 109.4 | 312.4 | n/a | n/a | n/a |
