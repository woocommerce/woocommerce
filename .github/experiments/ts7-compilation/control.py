from pathlib import Path
import json,os,subprocess,sys
import harness as h
D=Path(os.environ['TS7_DATA_DIR']);HERE=Path(__file__).resolve().parent
subprocess.run([sys.executable,str(HERE/'configure.py'),'ts5-adapted'],check=True)
r=h.measure('admin','ts5','control-final-config-ts5')
assert r['exit']==0, 'Adapted TS5 config must preserve a passing baseline'
a=json.loads((D/'trial-admin-1-ts5/outputs.json').read_text())
b=json.loads((D/'control-final-config-ts5/outputs.json').read_text())
a={k:v for k,v in a.items() if k.endswith('.d.ts')}
b={k:v for k,v in b.items() if k.endswith('.d.ts')}
assert a==b, 'Adapted resolver changes TS5 declarations'
print('Adapted TS5 control passes with identical declarations')
