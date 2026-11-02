import os
from pathlib import Path
import collections,json,re,statistics,hashlib
D=Path(os.environ['TS7_DATA_DIR'])
rows=json.loads((D/'measurements.json').read_text());assert len(rows)==27
result={}
for w in ['data','email','admin']:
 result[w]={}
 for compiler in ['ts5','ts7','next']:
  selected=sorted([r for r in rows if r['workload']==w and r['compiler']==compiler],key=lambda r:r['trial']);assert [r['trial'] for r in selected]==[1,2,3]
  outputs=[];diagnostics=[];source=[]
  for r in selected:
   p=D/r['label'];raw=(p/'resources.txt').read_text();assert r['seconds']==float(re.search(r'([\d.]+) real',raw)[1]);assert r['rss_bytes']==int(re.search(r'(\d+)\s+maximum resident set size',raw)[1])
   outputs.append(json.loads((p/'outputs.json').read_text()));diagnostics.append(((p/'stdout.txt').read_bytes(),(p/'stderr.txt').read_bytes()));source.append(json.loads((p/'inputs.json').read_text()))
  assert all(x==outputs[0] for x in outputs),'Nondeterministic output '+w+'/'+compiler
  assert all(x==diagnostics[0] for x in diagnostics),'Nondeterministic diagnostics '+w+'/'+compiler
  assert all(x==source[0] for x in source),'Input drift '+w+'/'+compiler
  assert len({r['exit'] for r in selected})==1
  result[w][compiler]={'seconds':[r['seconds'] for r in selected],'median_seconds':statistics.median(r['seconds'] for r in selected),'rss_bytes':[r['rss_bytes'] for r in selected],'median_rss_bytes':statistics.median(r['rss_bytes'] for r in selected),'exit':selected[0]['exit'],'diagnostics':selected[0]['diagnostics'],'projects':selected[0]['projects'],'root_files':selected[0]['root_files'],'outputs':len(outputs[0]),'declarations':sum(f.endswith('.d.ts') for f in outputs[0]),'repeated_outputs_and_diagnostics_identical':True}
 for compiler in ['ts7','next']:
  result[w][compiler]['speedup']=result[w]['ts5']['median_seconds']/result[w][compiler]['median_seconds']
  result[w][compiler]['saved_seconds']=result[w]['ts5']['median_seconds']-result[w][compiler]['median_seconds']
  a=json.loads((D/f'trial-{w}-1-ts5/outputs.json').read_text());b=json.loads((D/f'trial-{w}-1-{compiler}/outputs.json').read_text())
  result[w][compiler]['declaration_membership_identical']={f for f in a if f.endswith('.d.ts')}=={f for f in b if f.endswith('.d.ts')}
  result[w][compiler]['changed_declarations']=[f for f in a if f.endswith('.d.ts') and a[f]!=b.get(f)]
(D/'summary.json').write_text(json.dumps(result,indent=2))
for w,arms in result.items():
 print(w)
 for c,x in arms.items():print(c,'median',x['median_seconds'],'range',min(x['seconds']),max(x['seconds']),'RSSGiB',round(x['median_rss_bytes']/2**30,2),'exit',x['exit'],'diagnostics',x['diagnostics'],'speedup',round(x.get('speedup',1),2),'changed declarations',len(x.get('changed_declarations',[])))
