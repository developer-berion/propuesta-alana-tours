<?php
// send-approval-email.php
// Backend script to send approval notification via SMTP
// NO CREDENTIALS EXPOSED TO FRONTEND

header('Content-Type: application/json');

// Configuration
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'contact@berioncompany.com');
define('SMTP_PASS', '&nbQV9@u'); // Password provided by user
define('SMTP_FROM', 'contact@berioncompany.com');
define('SMTP_TO', 'contact@berioncompany.com');
define('SMTP_SUBJECT', 'Nueva Propuesta Aprobada - Alana Tours');

class SMTPMailer {
    private $socket;
    private $log = [];

    public function send($to, $subject, $message) {
        $this->log = [];
        
        try {
            $this->connect();
            $this->auth();
            $this->sendMail($to, $subject, $message);
            $this->quit();
            return ['success' => true, 'message' => 'Email sent'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage(), 'log' => $this->log];
        }
    }

    private function connect() {
        $socketContext = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $this->socket = stream_socket_client('ssl://' . SMTP_HOST . ':' . SMTP_PORT, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $socketContext);
        
        if (!$this->socket) {
            throw new Exception("Connection failed: $errstr ($errno)");
        }
        $this->readResponse();
        $this->sendCommand('EHLO ' . $_SERVER['SERVER_NAME']);
    }

    private function auth() {
        $this->sendCommand('AUTH LOGIN');
        $this->sendCommand(base64_encode(SMTP_USER));
        $this->sendCommand(base64_encode(SMTP_PASS));
    }

    private function sendMail($to, $subject, $body) {
        $this->sendCommand('MAIL FROM: <' . SMTP_FROM . '>');
        $this->sendCommand('RCPT TO: <' . $to . '>');
        $this->sendCommand('DATA');

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/plain; charset=UTF-8\r\n";
        $headers .= "From: Alana Tours <" . SMTP_FROM . ">\r\n";
        $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
        $headers .= "X-Mailer: BerionSMTP/1.0\r\n";
        $headers .= "Subject: $subject\r\n"; // Subject line must be part of headers in DATA block

        // Split body to ensure line lengths compliant with RFC (optional but good practice) however lightweight is asked
        // We will just put the message.
        $emailContent = "$headers\r\n$body\r\n.\r\n";

        fwrite($this->socket, $emailContent);
        $this->readResponse();
    }

    private function sendCommand($cmd) {
        fwrite($this->socket, $cmd . "\r\n");
        $this->readResponse();
    }

    private function readResponse() {
        $response = '';
        while ($str = fgets($this->socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') {
                break;
            }
        }
        $this->log[] = $response;
        // Simple check for error codes (4xx or 5xx)
        if (substr($response, 0, 1) >= 4) {
            throw new Exception("SMTP Error: " . $response);
        }
    }

    private function quit() {
        fwrite($this->socket, "QUIT\r\n");
        fclose($this->socket);
    }
}

// Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mailer = new SMTPMailer();
    $body = "Hola,\n\nSe ha registrado una nueva aprobación de la propuesta comercial para Alana Tours.\n\nFecha: " . date('Y-m-d H:i:s') . "\n\nPor favor contactar al cliente para formalizar.\n\nAtte,\nSistema Berion Company";
    
    $result = $mailer->send(SMTP_TO, SMTP_SUBJECT, $body);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid Request Method']);
}
?>
