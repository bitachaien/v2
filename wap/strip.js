const fs = require('fs');
const path = require('path');

function removeLineComments(code) {
  const lines = code.split('\n');
  return lines.map(line => {
    const trimmed = line.trim();
    if (trimmed.startsWith('//')) return '';
    return line;
  }).join('\n');
}

function removeBlockComments(code) {
  return code.replace(/\/\*[\s\S]*?\*\//g, '');
}

function removeEmptyLines(code) {
  return code.replace(/\n{3,}/g, '\n\n');
}

function processJs(code) {
  let result = removeBlockComments(code);
  result = removeLineComments(result);
  result = removeEmptyLines(result);
  return result;
}

function processVue(content) {
  let result = content;
  
  const scriptStart = result.indexOf('<script');
  if (scriptStart !== -1) {
    const scriptTagEnd = result.indexOf('>', scriptStart);
    const scriptEnd = result.indexOf('</script>', scriptTagEnd);
    if (scriptTagEnd !== -1 && scriptEnd !== -1) {
      const before = result.substring(0, scriptTagEnd + 1);
      const script = result.substring(scriptTagEnd + 1, scriptEnd);
      const after = result.substring(scriptEnd);
      result = before + processJs(script) + after;
    }
  }
  
  const styleStart = result.indexOf('<style');
  if (styleStart !== -1) {
    const styleTagEnd = result.indexOf('>', styleStart);
    const styleEnd = result.indexOf('</style>', styleTagEnd);
    if (styleTagEnd !== -1 && styleEnd !== -1) {
      const before = result.substring(0, styleTagEnd + 1);
      const style = result.substring(styleTagEnd + 1, styleEnd);
      const after = result.substring(styleEnd);
      result = before + processCss(style) + after;
    }
  }
  
  result = result.replace(/<!--[\s\S]*?-->/g, '');
  
  return result;
}

function processCss(code) {
  let result = code.replace(/\/\*[\s\S]*?\*\//g, '');
  result = removeLineComments(result);
  return removeEmptyLines(result);
}

function processFile(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  const content = fs.readFileSync(filePath, 'utf8');
  let newContent;
  
  if (ext === '.vue') {
    newContent = processVue(content);
  } else if (ext === '.js') {
    newContent = processJs(content);
  } else if (['.css', '.less', '.scss'].includes(ext)) {
    newContent = processCss(content);
  } else {
    return false;
  }
  
  if (newContent !== content) {
    fs.writeFileSync(filePath, newContent);
    return true;
  }
  return false;
}

function walk(dir, cb) {
  for (const f of fs.readdirSync(dir)) {
    const p = path.join(dir, f);
    if (fs.statSync(p).isDirectory()) {
      if (!['node_modules', 'dist', '.git'].includes(f)) walk(p, cb);
    } else cb(p);
  }
}

const src = path.join(__dirname, 'src');
let n = 0, m = 0;
walk(src, f => {
  if (/\.(vue|js|css|less|scss)$/.test(f)) {
    n++;
    if (processFile(f)) {
      m++;
      console.log('OK:', f.replace(src, ''));
    }
  }
});
console.log(`Done: ${m}/${n} modified`);
