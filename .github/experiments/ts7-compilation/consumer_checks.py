import os,re
from pathlib import Path
import json,shutil,subprocess
import harness as h
D=Path(os.environ['TS7_DATA_DIR'])
C=h.SOURCE/'.ts7-consumers';C.mkdir(exist_ok=True)
(C/'optional-property.ts').write_text("import { cleanQuery } from '../packages/js/data/build-types/crud/utils';\nconst query: Pick<ReturnType<typeof cleanQuery>, 'context'> = { context: undefined };\nvoid query;\n")
(C/'react-constructor.ts').write_text("import ImageUpload from '../packages/js/components/build-types/image-upload';\nconst upload = new ImageUpload({});\nvoid upload;\n")
records=[]
for producer in ['ts5','ts7','next']:
    source=D/f'trial-admin-1-{producer}'/'emitted'
    assert source.is_dir()
    h.reset_outputs(h.graph(h.ENTRIES['admin']))
    for f in source.rglob('*'):
        if f.is_file():
            target=h.SOURCE/f.relative_to(source);target.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(f,target)
    for case in ['optional-property','react-constructor','ambient-declaration']:
        target=D/f'consumer-{producer}-{case}';target.mkdir()
        file=C/(case+'.ts') if case!='ambient-declaration' else h.SOURCE/'packages/js/email-editor/build-types/private-apis/index.d.ts'
        cmd=h.RUNTIMES['ts5']+['--noEmit','--pretty','false','--strict','--exactOptionalPropertyTypes','--esModuleInterop','--moduleResolution','node','--module','commonjs','--target','esnext','--types','node']
        if case!='ambient-declaration':cmd+=['--skipLibCheck']
        cmd.append(str(file))
        r=subprocess.run(cmd,cwd=h.SOURCE,text=True,capture_output=True,timeout=120)
        (target/'stdout.txt').write_text(r.stdout);(target/'stderr.txt').write_text(r.stderr)
        row={'producer':producer,'consumer_compiler':'TypeScript5.7.3','case':case,'exit':r.returncode,'command':cmd,'diagnostics':r.stdout};records.append(row)
        print(json.dumps(row),flush=True)
(D/'consumer-results.json').write_text(json.dumps(records,indent=2))

expected={('ts5','optional-property'):0,('ts5','react-constructor'):0,('ts5','ambient-declaration'):0,('ts7','optional-property'):2,('ts7','react-constructor'):2,('ts7','ambient-declaration'):0,('next','optional-property'):2,('next','react-constructor'):2,('next','ambient-declaration'):0}
assert {(r['producer'],r['case']):r['exit'] for r in records}==expected, 'Consumer compatibility outcome changed; inspect results'

for row in records:
    codes=re.findall(r'error (TS\d+):',row['diagnostics'])
    wanted=[] if row['exit']==0 else ['TS2375' if row['case']=='optional-property' else 'TS2554']
    assert codes==wanted, 'Unexpected consumer error; inspect diagnostic rather than treating any failure as expected'
