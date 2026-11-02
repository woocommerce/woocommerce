import os
from pathlib import Path
import json,subprocess,sys,hashlib
import harness as h
D=Path(os.environ['TS7_DATA_DIR'])
records=[]
expected={'data':(4,252),'email':(1,127),'admin':(18,1687)}
roots={}
for workload in ['data','email','admin']:
    for trial in range(1,4):
        order=[['ts5','ts7','next'],['next','ts5','ts7'],['ts7','next','ts5']][trial-1]
        for compiler in order:
            mode='original' if compiler=='ts5' else 'native'
            subprocess.run([sys.executable,str(Path(__file__).resolve().parent/'configure.py'),mode],check=True)
            graph=h.graph(h.ENTRIES[workload]); actual={str(Path(c['config']).relative_to(h.SOURCE)):sorted(str(Path(f).relative_to(h.SOURCE)) for f in c['files']) for c in graph}
            if workload not in roots:roots[workload]=actual
            assert actual==roots[workload], 'Compiler configs changed source coverage'
            assert (len(graph),sum(len(c['files']) for c in graph))==expected[workload]
            row=h.measure(workload,compiler,f'trial-{workload}-{trial}-{compiler}')
            row['trial']=trial;records.append(row)
            (D/'measurements.json').write_text(json.dumps(records,indent=2))
            if workload!='admin' or compiler=='ts5':assert row['exit']==0, 'Expected passing workload failed'
(D/'root-parity.json').write_text(json.dumps(roots,indent=2))
print('Completed all 27 measurements',flush=True)
