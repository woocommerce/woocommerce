import os
from pathlib import Path
import hashlib,json,os,re,subprocess,sys,shutil
import harness as h
D=Path(os.environ['TS7_DATA_DIR']);HERE=Path(__file__).resolve().parent
w=sys.argv[1];label=sys.argv[2];out=D/label;out.mkdir();phases=[]
subprocess.run([sys.executable,str(HERE/'configure.py'),'original'],check=True)
graph=h.graph(h.ENTRIES[w]);h.reset_outputs(h.graph(h.ENTRIES['admin']))
for phase,compiler,flags in [('emit','ts5',['--emitDeclarationOnly','--noCheck']),('check','next',['--noEmit'])]:
 if phase=='check':subprocess.run([sys.executable,str(HERE/'configure.py'),'native'],check=True)
 cmd=h.RUNTIMES[compiler]+['--build',h.ENTRIES[w],'--force','--pretty','false']+flags
 cache=Path(h.LOCATION['root'])/'runtime-caches'/label/phase;cache.mkdir(parents=True)
 env=os.environ.copy();env.pop('NODE_OPTIONS',None);env.pop('GOMAXPROCS',None);env.pop('GOMEMLIMIT',None);env['NODE_COMPILE_CACHE']=str(cache)
 with (out/(phase+'-stdout.txt')).open('w') as stdout,(out/(phase+'-stderr.txt')).open('w') as stderr:
  r=subprocess.run(['/usr/bin/time','-l','-o',str(out/(phase+'-resources.txt')),*cmd],cwd=h.SOURCE,env=env,stdout=stdout,stderr=stderr,timeout=600)
 raw=(out/(phase+'-resources.txt')).read_text();metric={'phase':phase,'exit':r.returncode,'seconds':float(re.search(r'([\d.]+) real',raw)[1]),'rss_bytes':int(re.search(r'(\d+)\s+maximum resident set size',raw)[1]),'command':cmd}
 phases.append(metric);print(json.dumps(metric),flush=True)
 manifest={k:v for k,v in h.outputs(graph).items() if k.endswith(('.d.ts','.d.ts.map'))}
 (out/(phase+'-outputs.json')).write_text(json.dumps(manifest,indent=2))
 if phase=='emit':
  before=manifest
  baseline=json.loads((D/f'trial-{w}-1-ts5/outputs.json').read_text());baseline={k:v for k,v in baseline.items() if k.endswith(('.d.ts','.d.ts.map'))}
  assert set(manifest)==set(baseline),'NoCheck changes output membership'
  changed=[k for k in manifest if manifest[k]!=baseline[k]]
  (out/'changed-declarations.json').write_text(json.dumps(changed,indent=2))
  for name in manifest:
   dest=out/'emitted'/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(h.SOURCE/name,dest)
  assert r.returncode==0
 else:assert manifest==before,'Native noEmit changed declarations'
result={'workload':w,'phases':phases,'seconds':sum(x['seconds'] for x in phases),'peak_rss_bytes':max(x['rss_bytes'] for x in phases),'declarations_identical_to_ts5':not changed,'changed_declarations':changed}
(out/'metrics.json').write_text(json.dumps(result,indent=2));print(json.dumps(result),flush=True)
