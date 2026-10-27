const http = require('http');
const fs = require('fs');
const path = require('path');

const root = __dirname;
const port = 8000;

const TYPES = {
  '.html': 'text/html; charset=utf-8',
  '.js':   'text/javascript; charset=utf-8',
  '.json': 'application/json; charset=utf-8',
  '.sb3':  'application/x-scratch-project',
  '.png':  'image/png',
  '.jpg':  'image/jpeg',
  '.jpeg': 'image/jpeg',
  '.svg':  'image/svg+xml',
  '.wav':  'audio/wav',
};

http.createServer((req, res) => {
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, OPTIONS');
  if (req.method === 'OPTIONS') { res.end(); return; }

  let p = decodeURIComponent(new URL(req.url, 'http://127.0.0.1').pathname);
  if (p === '/' || p === '') p = '/index.html';

  const file = path.join(root, p);
  if (!file.startsWith(root)) { res.statusCode = 403; res.end('forbidden'); return; }

  fs.readFile(file, (err, data) => {
    if (err) { res.statusCode = 404; res.end('not found'); return; }
    res.setHeader('Content-Type', TYPES[path.extname(file).toLowerCase()] || 'application/octet-stream');
    res.end(data);
  });
}).listen(port, '127.0.0.1', () => console.log(`Server: http://127.0.0.1:${port}`));
