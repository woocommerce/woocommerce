import hashlib,json,os,shutil,subprocess,tempfile
from pathlib import Path
D=Path(os.environ['TS7_DATA_DIR'])
original=Path(os.environ['TS7_SOURCE']).resolve()
root=Path(tempfile.mkdtemp(prefix='wc-ts7-',dir=os.environ['TMPDIR'])).resolve(); source=root/'source';source.mkdir();source=source.resolve();tools=root/'tools';tools.mkdir()
(D/'location.json').write_text(json.dumps({'root':str(root),'source':str(source),'tools':str(tools),'original_source':str(original)},indent=2))
tracked=subprocess.check_output(['git','ls-files','-z'],cwd=original).decode().split('\0');manifest={}
for f in filter(None,tracked):
 a=original/f;b=source/f;b.parent.mkdir(parents=True,exist_ok=True)
 if a.is_symlink():b.symlink_to(os.readlink(a))
 else:shutil.copy2(a,b);manifest[f]=hashlib.sha256(a.read_bytes()).hexdigest()
(D/'tracked-source-manifest.json').write_text(json.dumps(manifest,indent=2))
deps=[]
for directory,dirs,files in os.walk(original,followlinks=False):
 dirs[:]=[x for x in dirs if x not in ['.git','.agents','.claude','vendor']]
 if 'node_modules' in dirs:
  a=Path(directory)/'node_modules';dirs.remove('node_modules')
  if str(a.parent.relative_to(original)/'package.json') in manifest:deps.append(a)
for i,a in enumerate(deps):
 b=source/a.relative_to(original);b.parent.mkdir(parents=True,exist_ok=True)
 subprocess.run(['cp','-cR',str(a),str(b)],check=True)
 print('Copied dependency directory',i+1,len(deps),flush=True)
fixed=[];external=[];links=0
for directory,dirs,files in os.walk(source,followlinks=False):
 for name in dirs+files:
  f=Path(directory)/name
  if not f.is_symlink():continue
  links+=1;target=os.readlink(f)
  target_path=Path(target)
  if target_path.is_absolute() and target_path.resolve().is_relative_to(original):
   new=source/target_path.resolve().relative_to(original);f.unlink();f.symlink_to(new);fixed.append(str(f.relative_to(source)))
  if not f.resolve().is_relative_to(source):external.append(str(f.relative_to(source)))
(D/'symlink-audit.json').write_text(json.dumps({'count':links,'retargeted':fixed,'external':external},indent=2));assert not external,external
assert (source/'pnpm-lock.yaml').read_bytes()==(source/'node_modules/.pnpm/lock.yaml').read_bytes()
print('Snapshot ready',source,flush=True)
