<?php
/**
 * @package	HikaShop
 * @version	6.5.0
 * @author	hikashop.com
 * @copyright	(C) 2010-2026 HIKARI SOFTWARE. All rights reserved.
 * @license	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or defined('ABSPATH') or die('Restricted access');
// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
?><?php
if(version_compare(PHP_VERSION, '8.1.0', '>=')) {

	class HikashopMcpTransport implements \Mcp\Server\Transport\TransportInterface {
		private $onMessage;
		private $outgoingMessagesProvider;
		private $sessionId;
		private $isSse = false;


		public function initialize(): void {
			if ((isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'text/event-stream') !== false) || isset($_GET['sse'])) {
				$this->isSse = true;
				if(!headers_sent()) {
					header('Content-Type: text/event-stream');
					header('Cache-Control: no-cache');
					header('Connection: keep-alive');
					header('X-Accel-Buffering: no'); // Nginx
				}
				$endpoint = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . htmlspecialchars($_SERVER['HTTP_HOST'], ENT_QUOTES, 'UTF-8') . htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
				$this->sendSseEvent('endpoint', $endpoint);
			}
		}



		private function dbg($msg) {
			file_put_contents(__DIR__ . '/debug_mcp.log', date('Y-m-d H:i:s') . " " . $msg . "\n", FILE_APPEND);
		}

		public function listen(): mixed {
			if ($this->isSse) {
				$start = time();
				@set_time_limit(0);

				while (true) {
					if (connection_aborted()) break;

					if ($this->outgoingMessagesProvider && $this->sessionId) {
						$messages = ($this->outgoingMessagesProvider)($this->sessionId);
						foreach ($messages as $msg) {
							$this->sendSseEvent('message', $msg['message']);
						}
					}

					if (ob_get_level() > 0) ob_flush();
					flush();
					sleep(1);
				}
			} else {
				$input = file_get_contents('php://input');
				$this->dbg("MCP Listen Input: " . $input);

				if(empty($input)) {
					if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
						if(!headers_sent()) header('HTTP/1.0 405 Method Not Allowed');
						echo json_encode(['jsonrpc' => '2.0', 'error' => ['code' => -32600, 'message' => 'Method not allowed. Use POST keys.'], 'id' => null]);
					} else {
						if(!headers_sent()) header('HTTP/1.0 400 Bad Request');
						echo json_encode(['jsonrpc' => '2.0', 'error' => ['code' => -32700, 'message' => 'Parse error: Empty request body.'], 'id' => null]);
					}
					return null;
				}

				if ($input && $this->onMessage) {
					try {
						$rpc = json_decode($input, true);
						$method = isset($rpc['method']) ? $rpc['method'] : '';

						if ($method !== 'initialize') {
							$fakeInit = json_encode([
								'jsonrpc' => '2.0', 'id' => 'auto-init', 'method' => 'initialize',
								'params' => [
									'protocolVersion' => '2024-11-05',
									'capabilities' => (object)[], // Must be object
									'clientInfo' => ['name' => 'StatelessClient', 'version' => '1.0']
								]
							]);

							$initSessionId = null; 
							($this->onMessage)($fakeInit, $initSessionId);

							if (!$this->sessionId) {
								$this->sessionId = \Symfony\Component\Uid\Uuid::v4();
							}

							$fakeInitNotify = json_encode([
								'jsonrpc' => '2.0', 'method' => 'notifications/initialized'
							]);
							($this->onMessage)($fakeInitNotify, $this->sessionId);
						}

						($this->onMessage)($input, $this->sessionId);

						if ($this->outgoingMessagesProvider && $this->sessionId) {
							$messages = ($this->outgoingMessagesProvider)($this->sessionId);
							foreach ($messages as $msg) {
								$this->send($msg['message'], isset($msg['context']) ? $msg['context'] : []);
							}
						}

					} catch (\Throwable $e) {
						$this->dbg("MCP Exception: " . $e->getMessage());
						echo json_encode(['jsonrpc' => '2.0', 'error' => ['code' => -32603, 'message' => 'Internal Error: ' . $e->getMessage()], 'id' => null]);
						throw $e;
					}
				} else {
					$this->dbg("No onMessage handler set!");
				}
			}
			return null;
		}

		public function send(string $data, array $context): void {
			$this->dbg("MCP Send called. Data len: " . strlen($data));
			if ($this->isSse) {
				$this->sendSseEvent('message', $data);
			} else {
				if(!headers_sent()) {
					header('Content-Type: application/json');
				}
				echo $data;
			}
		}

		private function sendSseEvent($event, $data) {
			echo "event: $event\n";
			echo "data: $data\n\n";
		}

		public function close(): void {}
		public function onMessage(callable $listener): void { $this->onMessage = $listener; } 
		public function onSessionEnd(callable $listener): void {}
		public function setOutgoingMessagesProvider(callable $provider): void { $this->outgoingMessagesProvider = $provider; }
		public function setPendingRequestsProvider(callable $provider): void {}
		public function setResponseFinder(callable $finder): void {}
		public function setFiberYieldHandler(callable $handler): void {}
		public function attachFiberToSession(\Fiber $fiber, \Symfony\Component\Uid\Uuid $sessionId): void {} 
		public function setSessionId(?\Symfony\Component\Uid\Uuid $sessionId): void { $this->sessionId = $sessionId; }
	}
}
