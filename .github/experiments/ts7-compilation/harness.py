import hashlib
import json
import os
from pathlib import Path
import re
import shutil
import subprocess
import time

DATA = Path(os.environ['TS7_DATA_DIR'])
LOCATION = json.loads((DATA / 'location.json').read_text())
SOURCE = Path(LOCATION['source']).resolve()
NODE = Path(os.environ['TS7_NODE'])
TOOLS = Path(LOCATION['tools'])
COMPILER = SOURCE / 'node_modules/typescript/lib/tsc.js'
RUNTIMES = {'ts5': [str(NODE),str(COMPILER)], 'ts7': [str(TOOLS/'7.0.2/node_modules/@typescript/typescript-darwin-arm64/lib/tsc')], 'next': [str(TOOLS/'7.1.0-dev.20261101.1/node_modules/@typescript/typescript-darwin-arm64/lib/tsc')]}
ENTRIES = {'currency': 'packages/js/currency/tsconfig.json', 'admin': 'plugins/woocommerce/client/admin/tsconfig.json', 'components':'packages/js/components/tsconfig.json', 'data':'packages/js/data/tsconfig.json', 'email':'packages/js/email-editor/tsconfig.json'}

def digest(path):
    return hashlib.sha256(path.read_bytes()).hexdigest()

def node_json(code, *args, cwd=SOURCE):
    return json.loads(subprocess.check_output([str(NODE), '-e', code, *args], cwd=cwd, text=True))

def graph(entry, directory=SOURCE):
    return node_json(r'''
    const ts = require('./node_modules/typescript');
    const path = require('path');
    const seen = new Set(), result = [];
    function visit(file) {
      file = path.resolve(file);
      if (seen.has(file)) return;
      seen.add(file);
      const config = ts.getParsedCommandLineOfConfigFile(file, {emitDeclarationOnly: true}, {...ts.sys, onUnRecoverableConfigFileDiagnostic: d => {throw Error(ts.flattenDiagnosticMessageText(d.messageText, '\n'))}});
      if (!config || config.errors.length) throw Error('Invalid configuration '+file);
      for (const ref of config.projectReferences || []) visit(ts.resolveProjectReferencePath(ref));
      result.push({config:file, files:config.fileNames, noEmit:!!config.options.noEmit,
        dirs:[config.options.outDir, config.options.declarationDir].filter(Boolean),
        buildinfo:ts.getTsBuildInfoEmitOutputFilePath(config.options)});
    }
    visit(process.argv[1]);
    console.log(JSON.stringify(result));
    ''', entry, cwd=directory)

def owned(path):
    path = Path(path)
    resolved = path.resolve()
    if resolved == SOURCE or not resolved.is_relative_to(SOURCE) or 'node_modules' in path.relative_to(SOURCE).parts:
        raise RuntimeError('Unsafe output path: '+str(path))
    return path

def output_roots(projects):
    dirs = sorted({str(owned(p)) for c in projects for p in c['dirs']})
    infos = sorted({str(owned(c['buildinfo'])) for c in projects if c.get('buildinfo')})
    return dirs, infos

def reset_outputs(projects):
    dirs, infos = output_roots(projects)
    for directory in dirs:
        path = owned(directory)
        if path.exists(): shutil.rmtree(path)
    for item in infos:
        owned(item).unlink(missing_ok=True)

def outputs(projects):
    dirs, infos = output_roots(projects)
    files = {Path(p) for d in dirs if Path(d).is_dir() for p in Path(d).rglob('*') if p.is_file()}
    files.update(Path(p) for p in infos if Path(p).is_file())
    return {str(p.relative_to(SOURCE)): {'bytes':p.stat().st_size,'sha256':digest(p)} for p in sorted(files)}

def inputs(projects):
    paths = {Path(p) for c in projects for p in [c['config'], *c['files']]}
    paths.update(SOURCE / p for p in ['package.json','pnpm-lock.yaml','pnpm-workspace.yaml','node_modules/typescript/lib/tsc.js','node_modules/typescript/lib/_tsc.js'])
    return {str(p.relative_to(SOURCE)):digest(p) for p in sorted(paths)}

def measure(workload, compiler, label, force=True, extra=None):
    projects=graph(ENTRIES[workload]); initial=inputs(projects)
    reset_outputs(graph(ENTRIES['admin'])); assert outputs(projects)=={}
    target=DATA/label;target.mkdir()
    cache=Path(LOCATION['root'])/'runtime-caches'/label;cache.mkdir(parents=True)
    command=RUNTIMES[compiler]+['--build',ENTRIES[workload],'--emitDeclarationOnly','--pretty','false']
    if force:command.append('--force')
    command+=extra or []
    env=os.environ.copy()
    for key in ['NODE_OPTIONS','GOMAXPROCS','GOMEMLIMIT']:env.pop(key,None)
    env.update({'NODE_COMPILE_CACHE':str(cache),'CI':'true','NO_COLOR':'1'})
    version=subprocess.check_output(RUNTIMES[compiler]+['--version'],cwd=SOURCE,text=True).strip()
    with (target/'stdout.txt').open('wb') as out,(target/'stderr.txt').open('wb') as err:
        result=subprocess.run(['/usr/bin/time','-l','-o',str(target/'resources.txt'),*command],cwd=SOURCE,env=env,stdout=out,stderr=err,timeout=600)
    raw=(target/'resources.txt').read_text();manifest=outputs(projects)
    assert inputs(projects)==initial,'Compiler changed source/config inputs'
    (target/'outputs.json').write_text(json.dumps(manifest,indent=2))
    (target/'inputs.json').write_text(json.dumps(initial,indent=2))
    (target/'graph.json').write_text(json.dumps(projects,indent=2))
    for name in manifest:
        if name.endswith(('.d.ts','.d.ts.map','.js')):
            dest=target/'emitted'/name;dest.parent.mkdir(parents=True,exist_ok=True);shutil.copy2(SOURCE/name,dest)
    text=(target/'stdout.txt').read_text()
    metrics={'workload':workload,'compiler':compiler,'version':version,'label':label,'command':command,'exit':result.returncode,
      'seconds':float(re.search(r'([\d.]+) real',raw)[1]),'rss_bytes':int(re.search(r'(\d+)\s+maximum resident set size',raw)[1]),
      'diagnostics':len(re.findall(r'error TS\d+:',text)), 'root_files':sum(len(c['files']) for c in projects),'projects':len(projects),'output_files':len(manifest)}
    (target/'metrics.json').write_text(json.dumps(metrics,indent=2));print(json.dumps(metrics),flush=True)
    return metrics

if __name__=='__main__':
    import sys
    assert json.loads((DATA/'symlink-audit.json').read_text())['external']==[]
    measure(*sys.argv[1:4])
