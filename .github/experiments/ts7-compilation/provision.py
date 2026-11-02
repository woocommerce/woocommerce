from pathlib import Path
import json,urllib.request,hashlib,base64,tarfile,io,os,subprocess
D=Path(os.environ['TS7_DATA_DIR']);L=json.loads((D/'location.json').read_text());T=Path(L['tools']);records={}
for version in ['7.0.2','7.1.0-dev.20261101.1']:
 dest=T/version/'node_modules';dest.mkdir(parents=True)
 for name in ['typescript','@typescript/typescript-darwin-arm64']:
  with urllib.request.urlopen('https://registry.npmjs.org/'+name.replace('/','%2f')+'/'+version) as r:meta=json.load(r)
  with urllib.request.urlopen(meta['dist']['tarball']) as r:blob=r.read()
  alg,value=meta['dist']['integrity'].split('-',1);assert alg == 'sha512';assert base64.b64encode(hashlib.new(alg,blob).digest()).decode()==value
  target=dest/name;target.mkdir(parents=True)
  with tarfile.open(fileobj=io.BytesIO(blob),mode='r:gz') as archive:
   for member in archive.getmembers():
    parts=Path(member.name).parts;assert parts[0]=='package' and '..' not in parts
    member.name=str(Path(*parts[1:])) if len(parts)>1 else '.'
    archive.extract(member,target,filter='data')
  records[name+'@'+version]={'dist':meta['dist'],'path':str(target),'package':meta}
 print('Provisioned',version,flush=True)
(D/'provisioned.json').write_text(json.dumps(records,indent=2))
