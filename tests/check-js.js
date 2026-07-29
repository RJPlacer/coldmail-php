const fs = require('fs');
const path = require('path');

const projectRoot = path.resolve(__dirname, '..');
const pages = ['index.php', 'settings.php', 'history.php', 'suppression.php', 'campaign.php'];

for (const page of pages) {
  const source = fs.readFileSync(path.join(projectRoot, page), 'utf8');
  const scripts = [...source.matchAll(/<script>([\s\S]*?)<\/script>/gi)];
  if (!scripts.length) {
    throw new Error(`${page}: no inline script found`);
  }

  for (const [, scriptSource] of scripts) {
    const renderedScript = scriptSource.replace(/<\?php[\s\S]*?\?>/g, '"test-token"');
    // Parsing through Function validates syntax without running browser code.
    new Function(renderedScript);
  }
  process.stdout.write(`${page}: JavaScript syntax OK\n`);
}
