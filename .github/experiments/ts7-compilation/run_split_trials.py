import os
from pathlib import Path
import json,subprocess,sys
import harness as h
D=Path(os.environ['TS7_DATA_DIR']);HERE=Path(__file__).resolve().parent
records=[]
for trial in range(1,4):
 for arm in (['control','split'] if trial%2 else ['split','control']):
  if arm=='control':
   subprocess.run([sys.executable,str(HERE/'configure.py'),'original'],check=True)
   row=h.measure('email','ts5',f'split-control-{trial}');assert row['exit']==0
  else:
   label=f'split-email-{trial}'
   subprocess.run([sys.executable,str(HERE/'split_screen.py'),'email',label],check=True)
   row=json.loads((D/label/'metrics.json').read_text());assert all(x['exit']==0 for x in row['phases']);assert row['declarations_identical_to_ts5']
  row.update({'trial':trial,'arm':arm});records.append(row)
(D/'split-measurements.json').write_text(json.dumps(records,indent=2))
print('Completed split pipeline pairs')
