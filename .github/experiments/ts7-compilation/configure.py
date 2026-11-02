import os
from pathlib import Path
import json,sys,difflib
D=Path(os.environ['TS7_DATA_DIR']);L=json.loads((D/'location.json').read_text());S=Path(L['source']).resolve()
FILES=['packages/js/internal-build/configs/tsconfig.json','plugins/woocommerce/client/admin/tsconfig.json','packages/js/email-editor/tsconfig.json']
saved=D/'original-configs.json'
if not saved.exists():saved.write_text(json.dumps({f:(S/f).read_text() for f in FILES},indent=2))
original=json.loads(saved.read_text());mode=sys.argv[1]
if mode=='original':
 for f,t in original.items():(S/f).write_text(t)
elif mode in ['native','ts5-adapted']:
 patch=[]
 for f,t in original.items():
  obj=json.loads(t);c=obj['compilerOptions']
  if f!=FILES[2]:
   c.update({'moduleResolution':'bundler','resolvePackageJsonExports':False,'resolvePackageJsonImports':False,'types':['*'],'noUncheckedSideEffectImports':False})
  if f==FILES[1]:
   del c['baseUrl'];c['paths']={'~/*':['./client/*']}
  if f==FILES[2]:c.pop('downlevelIteration')
  if mode=='ts5-adapted' and f!=FILES[2]:c.pop('types')
  changed=json.dumps(obj,indent='\t')+'\n';(S/f).write_text(changed)
  patch.extend(difflib.unified_diff(t.splitlines(True),changed.splitlines(True),fromfile='a/'+f,tofile='b/'+f))
 (D/(mode+'-config.patch')).write_text(''.join(patch))
else:raise ValueError(mode)
print(mode,flush=True)
