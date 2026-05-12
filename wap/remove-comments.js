const fs = require('fs');
const path = require('path');

function removeJsComments(code) {
  let result = '';
  let i = 0;
  let inString = false;
  let stringChar = '';
  let inTemplate = false;
  
  while (i < code.length) {
    if (!inString && !inTemplate) {
      if (code[i] === '"' || code[i] === "'" || code[i] === '`') {
        stringChar = code[i];
        inString = code[i] !== '`';
        inTemplate = code[i] === '`';
        result += code[i];
        i++;
        continue;
      }
      if (code[i] === '/' && code[i + 1] === '/') {
        while (i < code.length && code[i] !== '\n') i++;
        continue;
      }
      if (code[i] === '/' && code[i + 1] === '*') {
        i += 2;
        while (i < code.length && !(code[i] === '*' && code[i + 1] === '/')) i++;
        i += 2;
        continue;
      }
    } else if (inString) {
      if (code[i] === '\\' && i + 1 < code.length) {
        result += code[i] + code[i + 1];
        i += 2;
        continue;
      }
      if (code[i] === stringChar) inString = false;
    } else if (inTemplate) {
      if (code[i] === '\\' && i + 1 < code.length) {
        result += code[i] + code[i + 1];
        i += 2;
        continue;
      }
      if (code[i] === '`') inTemplate = false;
    }
    result += code[i];
    i++;
  }
  return result;
}

function processVueFile(content) {
  const scriptMatch = content.match(/(<script[^>]*>)([\s\S]*?)(<\/script>)/i);
  let result = content;
  if (scriptMatch) {
    const newScript = scriptMatch[1] + removeJsComments(scriptMatch[2]) + scriptMatch[3];
    result = result.replace(scriptMatch[0], newScript);
  }
  return result;
}

function processFile(filePath) {
  const ext = path.extname(filePath).toLowerCase();
  let content = fs.readFileSync(filePath, 'utf8');
  let newContent;
  try {
    if (ext === '.vue') {
      newContent = processVueFile(content);
    } else if (['.js', '.ts'].includes(ext)) {
      newContent = removeJsComments(content);
    } else {
      return false;
    }
    if (newContent !== content) {
      fs.writeFileSync(filePath, newContent, 'utf8');
      return true;
    }
    return false;
  } catch (e) {
    console.error('Error:', filePath, e.message);
    return false;
  }
}

function walkDir(dir, cb) {
  fs.readdirSync(dir).forEach(file => {
    const p = path.join(dir, file);
    const s = fs.statSync(p);
    if (s.isDirectory() && file !== 'node_modules' && file !== 'dist') walkDir(p, cb);
    else if (s.isFile()) cb(p);
  });
}

const srcDir = path.join(__dirname, 'src');
let processed = 0, modified = 0;
console.log('Processing:', srcDir);
walkDir(srcDir, (f) => {
  if (['.vue', '.js', '.ts'].includes(path.extname(f).toLowerCase())) {
    processed++;
    if (processFile(f)) { modified++; console.log('OK:', f); }
  }
});
console.log('Done! Processed', processed, 'files, modified', modified);
