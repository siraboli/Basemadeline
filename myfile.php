<?php
ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ALL);
use danog\MadelineProto\API;
use danog\MadelineProto\EventHandler;
use danog\MadelineProto\Shutdown;
use danog\MadelineProto\Logger;
if (file_exists("../vendor/autoload.php")) {
    include("../vendor/autoload.php");
} else {
    if (!file_exists('madeline.php')) {
        copy('https://phar.madelineproto.xyz/madeline.php', 'madeline.php');
    }
    include 'madeline.php';
}
function closeConnection(string $message = 'OK', int $responseCode = 200): void
{
    if (PHP_SAPI === 'cli' || \headers_sent()) {
        return;
    }
    Logger::log($message, Logger::FATAL_ERROR);
    $buffer  = @\ob_get_clean() ?: '';
    $buffer .= '<html><body><h1>' . \htmlentities($message) . '</h1></body></html>';
    set_time_limit(0);
    session_write_close();
    ignore_user_abort(true);
    if (ob_get_length() > 0) {
        ob_end_clean();
    }
    ob_start();
    echo $buffer;
    $size = ob_get_length();
    header("Connection: close\r\n");
    header("Content-Encoding: none\r\n");
    header("Content-Length: $size");
    http_response_code($responseCode);
    ob_end_flush();
    @ob_flush();
    flush();
}
if (isset($_REQUEST['MadelineSelfRestart'])) {
    Logger::log("Self-restarted, restart token " . $_REQUEST['MadelineSelfRestart'], Logger::ERROR);
}
function xrmdir($dir)
{
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            xrmdir($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}
function toJSON($var, bool $pretty = true): string
{
    if (isset($var['request'])) {
        unset($var['request']);
    }
    $opts = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    $json = \json_encode($var, $opts | ($pretty ? JSON_PRETTY_PRINT : 0));
    $json = ($json !== '') ? $json : var_export($var, true);
    return $json;
}
function mediaTimeDeFormater($seconds)
{
    if (!is_numeric($seconds))
        throw new Exception("Invalid Parameter Type!");
    $ret = "";
    $hours = (string)floor($seconds / 3600);
    $secs = (string)$seconds % 60;
    $mins = (string)floor(($seconds - ($hours * 3600)) / 60);
    if (strlen($hours) == 1)
        $hours = "0" . $hours;
    if (strlen($secs) == 1)
        $secs = "0" . $secs;
    if (strlen($mins) == 1)
        $mins = "0" . $mins;
    if ($hours == 0)
        $ret = "$mins:$secs";
    else
        $ret = "$hours:$mins:$secs";
    return $ret;
}
class MyEventHandler extends EventHandler
{
    const ADMINS = ['1419905066']; // Your Numeric ID
    const OWNER = 1419905066; // Your Numeric ID
    const LOGS = 1419905066; // Your Numeric ID
    public $bot_id = 0;
    public $timen = 0;
    public $Processed_Updates = 0;
    public function auto_restart()
    {
        yield $this->restart();
        return 3600000;
    }
    public function onStart()
    {
        $this->timen = time();
        //$auto_res = new GenericLoop([$this, 'auto_restart'], 'auto restart');
        //$auto_res->start();
    }
    public function onUpdateNewChannelMessage(array $update): \Generator
    {
        return ($this->onUpdateNewMessage($update, true));
        unset($update);
    }
    public function getReportPeers() { return '@DevaBoli'; } // Your User ID
    
    public function onUpdateNewMessage(array $update, $channel = false): \Generator
    {
        $this->Processed_Updates = $this->Processed_Updates + 1;
        if ($update['message']['_'] === 'messageService' || $update['message']['_'] === 'messageEmpty' || time() - $update['message']['date'] > 2) {
            return;
        }
        if (!$channel) {
            $message = $update["message"] ?? null;
            $text = $message["message"] ?? null;
            $message_id = $message["id"] ?? 0;
            $reply_to_msg_id = $message["reply_to_msg_id"] ?? 0;
            $from_id = $message['from_id']['user_id'];
            
            try {
                if($text == 'ping') {
                    yield $this->messages->sendMessage([
                        'peer'            => $update,
                        'message'         => 'I am online! :)',
                    ]);
                }
                elseif ($text == 'restart'){
                    yield $this->messages->sendMessage([
                        'peer'            => $update,
                        'message'         => 'Restarted ...',
                    ]);
                    $this->restart();
                }
                elseif ($text == "use") {
					$bot = array(
                        'UpTime' => mediaTimeDeFormater(time() - $this->timen)
                    );
                    $MemoryUsages = array(
                        'Mem_usage' => round(memory_get_usage() / 1024 / 1024, 1) . ' MB',
                        'Max_mem_usage' => round(memory_get_peak_usage() / 1024 / 1024, 1) . ' MB',
                        'Allocated_mem' => round(memory_get_usage(true) / 1024 / 1024, 1) . ' MB',
                        'Max_allocated_mem' => round(memory_get_peak_usage(true) / 1024 / 1024, 1) . ' MB',
                    );
                    $text = "• Mem usage        : {$MemoryUsages['Mem_usage']}";
                    $text .= "\n• Max mem usage    : {$MemoryUsages['Max_mem_usage']}";
                    $text .= "\n• Allocated mem    : {$MemoryUsages['Allocated_mem']}";
                    $text .= "\n• Max allocated mem: {$MemoryUsages['Max_allocated_mem']}";
                    $text .= "\n• UpTime: {$bot['UpTime']}";
                    yield $this->messages->sendMessage(["peer" => $update, "message" => $text]);
                }
            }
                
             catch (throwable $error) {
                $error_message = $error->getMessage();
                $error_file = $error->getFile();
                $error_line = $error->getLine();
                yield $this->messages->setTyping(["peer" => Self::LOGS, "action" => ["_" => "sendMessageTypingAction"]]);
                yield $this->messages->sendMessage(["peer" => Self::LOGS, "message" => "■ Error Message: $error_message\n\n■ Error File: $error_file\n\n■ Error Line: $error_line"]);
                yield $this->messages->sendMessage(["peer" => $update, "message" => "■ Error Detected, Check Self Logs !"]);
                unset($error);
                unset($error_file);
                unset($error_line);
            }
            unset($update);
            unset($text);
            unset($message);
            unset($message_id);
            unset($reply_to_msg_id);
        } else {
            try {
                if ($update["message"]["_"] === "messageService" or $update["message"]["_"] === "messageEmpty" or time() - $update["message"]["date"] > 5) {
                    return;
                }
                $message = $update["message"] ?? null;
                $text = $message["message"] ?? null;
                $message_id = $message["id"] ?? 0;
                $reply_to_msg_id = $message["reply_to_msg_id"] ?? 0;
                $from_id = $message['from_id']['user_id'] ?? NULL;
                $me_id = $this->bot_id;
                
                if ($from_id == Self::OWNER or in_array($from_id, Self::ADMINS)) {
                    yield $this->onUpdateNewMessage($update);
                }
                unset($update);
                unset($text);
                unset($message);
                unset($message_id);
                unset($reply_to_msg_id);
                unset($info);
                unset($chatID);
                unset($type2);
                unset($me);
                unset($me_id);
            } catch (throwable $error) {
                $error_message = $error->getMessage();
                $error_file = $error->getFile();
                $error_line = $error->getLine();
                yield $this->messages->setTyping(["peer" => Self::LOGS, "action" => ["_" => "sendMessageTypingAction"]]);
                yield $this->messages->sendMessage(["peer" => Self::LOGS, "message" => "■ Error Message: $error_message\n\n■ Error File: $error_file\n\n■ Error Line: $error_line"]);
                yield $this->messages->sendMessage(["peer" => $update, "message" => "■ Error Detected, Check Self Logs !"]);
                unset($error);
                unset($error_file);
                unset($error_line);
            }
            unset($update);
        }
    }
}
$settings = ['serialization' => ['cleanup_before_serialization' => true,"serialization_interval" => 30,],'logger' => ['max_size' => 25 * 1024 * 1024,"logger_level" => 3,],'peer' => ['full_fetch' => false,'cache_all_peers_on_startup' => false,"full_info_cache_time" => 30],'db' => ['type'  => 'memory',]];
$bot = new \danog\MadelineProto\API('MrAmini.session', $settings);
$bot->startAndLoop(MyEventHandler::class);
?>
