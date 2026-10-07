import fs from 'node:fs';
import path from 'node:path';
import { createRequire } from 'node:module';

const frontend = path.resolve('shifoxona/frontend');
const require = createRequire(path.join(frontend, 'package.json'));
const { parse } = require('@babel/parser');

function splitDeclarations(source) {
    const tree = parse(source, { sourceType: 'module' });
    const changes = [];

    for (const statement of tree.program.body) {
        if (statement.type !== 'VariableDeclaration' || statement.declarations.length < 2) continue;
        const replacement = statement.declarations.map(declaration => statement.kind + ' ' + source.slice(declaration.start, declaration.end) + ';').join('\n');
        changes.push({ start: statement.start, end: statement.end, replacement });
    }

    for (const change of changes.reverse()) {
        source = source.slice(0, change.start) + change.replacement + source.slice(change.end);
    }
    return source;
}

function simplifyConditions(source) {
    const tree = parse(source, { sourceType: 'module' });
    const changes = [];
    function visit(node) {
        if (!node || typeof node !== 'object') return;
        if (node.type === 'IfStatement') {
            const call = node.test?.argument?.argument;
            if (node.test?.operator === '!' && node.test.argument?.type === 'AwaitExpression' && call?.callee?.name === 'confirmDelete') {
                changes.push({ start: node.start, end: node.end, replacement: 'const confirmed = ' + source.slice(call.start, call.end) + ';\nif (!confirmed) {\nreturn;\n}' });
                return;
            }
            if (node.consequent.type !== 'BlockStatement') {
                changes.push({ start: node.consequent.start, end: node.consequent.end, replacement: '{\n' + source.slice(node.consequent.start, node.consequent.end) + '\n}' });
            }
        }
        for (const [key, value] of Object.entries(node)) {
            if (key === 'consequent' && node.type === 'IfStatement' && node.consequent.type !== 'BlockStatement') continue;
            if (Array.isArray(value)) value.forEach(visit);
            else if (value && typeof value === 'object') visit(value);
        }
    }
    visit(tree.program);
    changes.sort((a,b) => b.start-a.start);
    for (const change of changes) source = source.slice(0, change.start) + change.replacement + source.slice(change.end);
    return source;
}

function visit(directory) {
    for (const name of fs.readdirSync(directory)) {
        const file = path.join(directory, name);
        if (fs.statSync(file).isDirectory()) {
            visit(file);
        } else if (name.endsWith('.vue')) {
            let source = fs.readFileSync(file, 'utf8');
            source = source.replace(/(<script setup>)([\s\S]*?)(<\/script>)/, (_, open, script, close) => open + simplifyConditions(splitDeclarations(script)) + close);
            source = source.replaceAll('await Promise.all([load(), loadLookups()]);', 'await loadLookups();\nawait load();');
            fs.writeFileSync(file, source);
        }
    }
}

visit(path.join(frontend, 'src'));
