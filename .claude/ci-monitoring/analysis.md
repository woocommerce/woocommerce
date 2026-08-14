## Why the nightly run fails so often

The nightly run fails far more often than any other run. This is not chance. Two separate
things cause it, and they stack.

Every number below counts **wp-env job starts**, not runs. The nightly starts many more
jobs than a pull request run, so raw counts would mislead.

| Trigger | wp-env job starts | `wordpress` retries | Rate per job | Jobs lost |
|---|---:|---:|---:|---:|
| Pull request and push | ~41,450 | 81 | 0.2% | 29 |
| Release checks | 306 | 7 | 2.3% | 0 |
| Nightly | 1,272 | 94 | **7.4%** | 40 |

The nightly is 3% of all wp-env job starts. It holds 58% of every job that a failed
wordpress.org download killed.

### Something changed on about 1 August

Split the same figures by month and the picture changes. Every trigger got roughly four
times worse at the same time.

| Trigger | Runs | July rate per job | Runs | August rate per job |
|---|---:|---:|---:|---:|
| Pull request and push | 1,784 | 0.09% (21 of ~23,550) | 1,361 | 0.33% (60 of ~17,965) |
| Release checks | 4 | 0% (0 of 204) | 2 | 6.9% (7 of 102) |
| Nightly, 46-job matrix only | 3 | 2.9% (4 of 138) | 14 | 12.3% (79 of 644) |

The pull request row carries the step-change on its own. It counts thousands of runs, and
it quadrupled.

The other two rows hold the matrix size fixed across the boundary, which is the only way to
ask whether fan-out caused the step. Both are thin. Only three nightlies ran the 46-job
matrix before 1 August, and they retried 1, 3, and 0 times; the fourteen August nightlies on
the same matrix retried a median of 5.5 times. Release checks tell the same story from four
July runs and two August runs. Two thin observations that agree are worth more than one, but
three nights cannot settle the question by themselves.

The runner Node version is ruled out. The 30 July nightly ran all 46 jobs on Node 24.18.0 at
2.9%, and the 13 August nightly ran mostly the same 24.18.0 at 11%. The cause of the step is
still open.

### Fan-out is probably not the mechanism

A stronger test needs no month boundary. Inside a nightly, the jobs do not all start
together. About 30 start within two minutes, and the rest trail in ones and twos. If
simultaneous starts caused the failures, the large waves would fail more often.

They do not. Across six nightlies and 276 job starts:

| Wave size | Job starts | Jobs lost | Rate |
|---|---:|---:|---:|
| 10 or more jobs starting in the same minute | 172 | 7 | 4.1% |
| Fewer than 10 | 104 | 6 | 5.8% |

A job that starts alone fails slightly more often than a job in the big wave. The counts are
small, but they point the wrong way for fan-out.

### The nightly gap is older than the step-change

The nightly ran about 33 times the pull request rate in July and about 37 times in August.
The gap is stable, so it is a property of the nightly, not of August.

The nightly differs from a pull request run in two ways at once. It starts 46 jobs
together instead of about 13 spread out, and it runs between 03:30 and 04:05 UTC. The wave
test above argues against the first. Nothing tests the second, because almost nothing else
runs in that window. See "Which hour is best" below.

A third difference is real but small. Job families that fetch a pre-release WordPress zip
fail about twice as often as families that fetch the default zip (14% against 7.5% in
August). Every family fails, including the ones that fetch exactly what a pull request run
fetches.

### Which hour is best

The data cannot answer this yet. Hours 03:00 and 04:00 UTC hold 15 and 36 pull request runs
out of about 3,145. There is no traffic to compare the nightly against. In the hours that
do have traffic, the `wordpress` rate is driven by single incidents: hour 15:00 looks bad
only because one run lost 21 jobs.

Moving the cron is also weaker than it sounds. The cron fires at 03:00, but the jobs queue,
and wp-env does not start downloading until 03:35 to 04:05. A twenty-minute shift moves
nothing.

To answer the question, add a small scheduled probe. Run one job every hour on
`ubuntu-latest` that fetches the same source list and records the result. A week of that
gives a real hourly profile at almost no cost.

### What actually fails

The retry loop tags these failures `reason=wordpress`. The word "timeout" misleads. Every
failed request dies in under half a second:

```text
code: 'ETIMEDOUT'
phases: { wait: 2, dns: 2, tcp: undefined, ..., total: 255 }
at internalConnectMultiple (node:net:1193:18)
at Timeout.internalConnectMultipleTimeout (node:net:1810:5)
```

DNS answers in 2 ms. The TCP connection never opens. Node gives up at 255 ms.

A real timeout takes ten seconds or more. The kernel alone retries a SYN after 1, 3, and 7
seconds. A failure at 255 ms is Node's own limit: `autoSelectFamilyAttemptTimeout` defaults
to 250 ms. Node tries each address in turn and abandons the connection when the list runs
out.

Both `wordpress.org` and `downloads.wordpress.org` publish one A record and one AAAA
record. GitHub runners have no working IPv6 route, so Node spends its first attempt on an
address that cannot answer.

wordpress.org is not down when this happens. In the nightly of 13 August, 41 of the 46 jobs
fetched the same files in the same minute without a single retry.

### The exact step that fails

Read in wp-env 11.9.0, the version this repository pins.

1. `lib/commands/start.js` calls `runtime.start()`.
2. `lib/runtime/docker/index.js:184` runs two things at once: it brings up the mysql
   container and calls `downloadSources()`. This is why the log shows the mysql container
   start and then the error. The spinner then reads `Stopped WordPress`, which comes from
   the cleanup handler in `start.js`, not from the step that failed.
3. `lib/runtime/docker/download-sources.js` collects every source with a URL and passes
   them to one `Promise.all`. There is no concurrency limit. For `.wp-env.e2e.json` that is
   **ten URLs at once**: the WordPress core zip, four plugin zips and three theme zips from
   wordpress.org, one mapped theme zip, and one archive from github.com.
4. `lib/download-sources.js` fetches each zip with `got.stream( source.url )`.

Three properties of that last call turn one bad connection into a dead job.

- **`got.stream()` never retries.** got 11 implements retries in its promise wrapper. The
  core only retries when something listens on its `retry` event
  (`got/dist/source/core/index.js:1227`), and the wrapper attaches that listener.
  `got.stream()` returns the bare core object, so nothing listens and nothing retries.
- **`Promise.all` fails on the first rejection.** Any one of the ten URLs kills the whole
  `wp-env start`.
- **The error never names the URL.** This is why CI can only report `reason=wordpress`, and
  why no one can tell which host refused the connection.

wp-env already ships a retry helper at `lib/retry.js`. It uses it for the WordPress
configuration step (`lib/runtime/docker/index.js:270` and `:284`) but not for downloads.

One more gap: `canAccessWPORG()` in `lib/wordpress.js:63` decides whether wp-env is offline
by resolving DNS only. DNS answers in 2 ms during these failures, so the check always
passes and never protects anything.

### Why three retries do not save the nightly

The loop waits 15 seconds, then 30 seconds. All three attempts finish inside about 100
seconds. On 13 August one job warned at 03:51:19, warned again at 03:51:50, and died at
03:52:36. Every attempt fell inside the same bad window.

### What to try

1. **Retry each download inside wp-env.** Wrap the `downloadSource()` call in
   `download-sources.js` with the existing `retry()` helper. This is a small upstream
   change, it targets the exact failing call, and it also fixes the case where one URL of
   ten fails. Include the URL in the error message while you are there.
2. **Set `NODE_OPTIONS=--no-network-family-autoselection`** on the wp-env start step. This
   tests the connect behaviour described above, and it is one line.
3. **Add the hourly probe** described above, so the question about time of day gets a real
   answer instead of an argument.
4. **Find out what changed on about 1 August.** Every trigger got four times worse at once.
   Nothing in this repository explains it yet.

Do not spend effort on staggering the jobs. The wave test argues against fan-out, and the
first item removes the need for it anyway.

Raise the backoff last. Today a longer wait spends more wall-clock time inside the same bad
window.

### Method and limits

- Job starts for the nightly and for release checks are exact counts from the jobs API.
- The pull request denominator is an estimate. A sample of 30 runs gave a mean of 13.2
  wp-env jobs per run.
- The nightly matrix grew from 22 to 36 jobs in July and to 46 jobs on 29 July. Comparisons
  across the month boundary use only the 46-job runs.
- The 22-job matrix ran once. It is excluded from every rate.
- Only three nightlies ran the 46-job matrix before 1 August. Every claim that rests on that
  row is suggestive, not settled.
- The wave test counts jobs whose wp-env step failed outright. It does not count jobs that
  retried and then passed, because the jobs API reports one conclusion per step.
- Release checks are the only large-matrix daytime control, and there are six of them.
- Figures cover 2026-07-15 to 2026-08-14.
