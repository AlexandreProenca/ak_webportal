/**
 * MCP Stdio <-> HTTP Bridge
 * Use this to connect tools like @modelcontextprotocol/inspector to your HTTP-based MCP server.
 * 
 * Usage:
 *   npx @modelcontextprotocol/inspector node mcp_bridge.js
 */

// You need to replace the following values with your own
const httpUrl = 'https://yourwebsite.com/index.php/hikashop-mcp';
const apiKey = 'your-api-key';


// ---------------------------------------------------------------------
// - Do not change anything below this line
// ---------------------------------------------------------------------

// We need to fetch and read streams. Node 18+ has native fetch.
// 1. Start SSE connection (GET with Accept: text/event-stream)
// 2. Listen to Stdin for JSON-RPC requests -> POST to URL
// 3. Listen to SSE events -> Write to Stdout

const { stdin, stdout } = process;

console.error(`[Bridge] Connecting to ${httpUrl}...`);

// --- 1. POST Request Handler (Stdin -> HTTP) ---
stdin.setEncoding('utf8');
let buffer = '';

stdin.on('data', (chunk) => {
    buffer += chunk;
    const lines = buffer.split('\n');
    buffer = lines.pop(); // Keep incomplete line

    for (const line of lines) {
        if (!line.trim()) continue;
        try {
            const request = JSON.parse(line);
            console.error(`[Bridge] Sending Request: ${request.method}`);
            sendPost(request);
        } catch (e) {
            console.error(`[Bridge] Failed to parse stdin line: ${e.message}`);
        }
    }
});

async function sendPost(payload) {
    try {
        const response = await fetch(httpUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-API-KEY': apiKey
            },
            body: JSON.stringify(payload)
        });

        if (!response.ok) {
            console.error(`[Bridge] POST Error: ${response.status} ${response.statusText}`);
            console.error(await response.text());
        } else {
            // SUCCESS: The server response (JSON-RPC) is in the body
            const text = await response.text();
            if (text) {
                // Split concatenated JSON objects (e.g. from Auto-Init)
                // "}{" -> "}\n{"
                const formatted = text.replace(/}{/g, '}\n{');
                console.error(`[Bridge] Sending to Inspector (${formatted.length} bytes):`);
                console.error(formatted);
                stdout.write(formatted + '\n');
            }
        }
    } catch (e) {
        console.error(`[Bridge] Network Error: ${e.message}`);
    }
}


// --- 2. SSE Listener (HTTP -> Stdout) ---
// Node.js native fetch streaming for SSE
(async () => {
    try {
        const response = await fetch(httpUrl, {
            headers: {
                'Accept': 'text/event-stream',
                'Cache-Control': 'no-cache',
                'X-API-KEY': apiKey
            }
        });

        if (!response.ok) {
            console.error(`[Bridge] SSE Connection Failed: ${response.status}`);
            process.exit(1);
        }

        console.error("[Bridge] Connected to SSE stream.");
        const reader = response.body.getReader();
        const decoder = new TextDecoder();
        let sseBuffer = '';

        while (true) {
            const { done, value } = await reader.read();
            if (done) break;

            const chunk = decoder.decode(value, { stream: true });
            sseBuffer += chunk;

            const events = sseBuffer.split('\n\n');
            sseBuffer = events.pop(); // Keep incomplete event

            for (const eventBlock of events) {
                parseSseEvent(eventBlock);
            }
        }
    } catch (e) {
        console.error(`[Bridge] SSE Error: ${e.message}`);
        process.exit(1);
    }
})();

function parseSseEvent(block) {
    const lines = block.split('\n');
    let eventName = 'message';
    let data = '';

    for (const line of lines) {
        if (line.startsWith('event: ')) {
            eventName = line.substring(7).trim();
        } else if (line.startsWith('data: ')) {
            data += line.substring(6); // Append data (multiline data usually usually separate data: lines, but simplified here)
        }
    }

    if (eventName === 'endpoint') {
        console.error(`[Bridge] Endpoint found: ${data}`);
    } else if (eventName === 'message') {
        // console.error(`[Bridge] Received: ${data}`);
        // Forward to Stdout for Inspector
        stdout.write(data + '\n');
    }
}
