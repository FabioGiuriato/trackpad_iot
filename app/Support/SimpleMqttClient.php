<?php

namespace App\Support;

use RuntimeException;

class SimpleMqttClient
{
    private mixed $socket = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly ?string $username,
        private readonly ?string $password,
        private readonly string $clientId,
        private readonly bool $verifyTls = false,
        private readonly int $keepAlive = 60,
    ) {
    }

    public function connect(): void
    {
        $context = stream_context_create([
            'ssl' => [
                'peer_name' => $this->host,
                'verify_peer' => $this->verifyTls,
                'verify_peer_name' => $this->verifyTls,
            ],
        ]);

        $this->socket = stream_socket_client(
            "tls://{$this->host}:{$this->port}",
            $errorCode,
            $errorMessage,
            20,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new RuntimeException("Connessione MQTT fallita: {$errorMessage} ({$errorCode})");
        }

        stream_set_timeout($this->socket, 2);

        $flags = 0x02; // clean session

        if ($this->username !== null && $this->username !== '') {
            $flags |= 0x80;
        }

        if ($this->password !== null && $this->password !== '') {
            $flags |= 0x40;
        }

        $variableHeader = $this->encodeString('MQTT')
            . chr(4)
            . chr($flags)
            . pack('n', $this->keepAlive);

        $payload = $this->encodeString($this->clientId);

        if (($flags & 0x80) === 0x80) {
            $payload .= $this->encodeString($this->username);
        }

        if (($flags & 0x40) === 0x40) {
            $payload .= $this->encodeString($this->password);
        }

        $this->writePacket(0x10, $variableHeader . $payload);
        $packet = $this->readPacket(true);

        if (!$packet || $packet['type'] !== 2 || strlen($packet['body']) < 2 || ord($packet['body'][1]) !== 0) {
            $code = $packet && strlen($packet['body']) >= 2 ? ord($packet['body'][1]) : 'nessuna risposta';

            throw new RuntimeException("CONNACK MQTT non valido: {$code}");
        }
    }

    public function subscribe(string $topic): void
    {
        $packetId = random_int(1, 65535);
        $body = pack('n', $packetId) . $this->encodeString($topic) . chr(0);

        $this->writePacket(0x82, $body);
        $packet = $this->readPacket(true);

        if (!$packet || $packet['type'] !== 9) {
            throw new RuntimeException('SUBACK MQTT non ricevuto.');
        }
    }

    public function listen(callable $onMessage): void
    {
        $lastPingAt = time();

        while (true) {
            $packet = $this->readPacket(false);

            if (!$packet) {
                if (time() - $lastPingAt >= max(10, $this->keepAlive - 10)) {
                    $this->writePacket(0xC0, '');
                    $lastPingAt = time();
                }

                continue;
            }

            if ($packet['type'] === 3) {
                [$topic, $payload] = $this->decodePublish($packet);
                $onMessage($topic, $payload);
            }

            if ($packet['type'] === 14) {
                throw new RuntimeException('Il broker MQTT ha chiuso la connessione.');
            }
        }
    }

    public function disconnect(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
        }

        $this->socket = null;
    }

    private function decodePublish(array $packet): array
    {
        $body = $packet['body'];
        $topicLength = unpack('n', substr($body, 0, 2))[1];
        $topic = substr($body, 2, $topicLength);
        $payloadOffset = 2 + $topicLength;
        $qos = ($packet['flags'] >> 1) & 0x03;

        if ($qos > 0) {
            $payloadOffset += 2;
        }

        return [$topic, substr($body, $payloadOffset)];
    }

    private function readPacket(bool $required): ?array
    {
        $firstByte = $this->readBytes(1, $required);

        if ($firstByte === null) {
            return null;
        }

        $remainingLength = 0;
        $multiplier = 1;

        do {
            $encodedByte = $this->readBytes(1, true);
            $value = ord($encodedByte);
            $remainingLength += ($value & 127) * $multiplier;
            $multiplier *= 128;
        } while (($value & 128) !== 0);

        $body = $remainingLength > 0 ? $this->readBytes($remainingLength, true) : '';
        $header = ord($firstByte);

        return [
            'type' => $header >> 4,
            'flags' => $header & 0x0F,
            'body' => $body,
        ];
    }

    private function readBytes(int $length, bool $required): ?string
    {
        $buffer = '';

        while (strlen($buffer) < $length) {
            $chunk = fread($this->socket, $length - strlen($buffer));

            if ($chunk === false || $chunk === '') {
                $meta = stream_get_meta_data($this->socket);

                if ($meta['eof'] ?? false) {
                    throw new RuntimeException('Connessione MQTT chiusa dal broker.');
                }

                if (!$required) {
                    return null;
                }

                if ($meta['timed_out'] ?? false) {
                    throw new RuntimeException('Timeout durante la lettura MQTT.');
                }

                throw new RuntimeException('Connessione MQTT chiusa dal broker.');
            }

            $buffer .= $chunk;
        }

        return $buffer;
    }

    private function writePacket(int $header, string $body): void
    {
        $packet = chr($header) . $this->encodeRemainingLength(strlen($body)) . $body;
        $written = fwrite($this->socket, $packet);

        if ($written === false || $written < strlen($packet)) {
            throw new RuntimeException('Scrittura MQTT fallita.');
        }
    }

    private function encodeString(string $value): string
    {
        return pack('n', strlen($value)) . $value;
    }

    private function encodeRemainingLength(int $length): string
    {
        $encoded = '';

        do {
            $byte = $length % 128;
            $length = intdiv($length, 128);

            if ($length > 0) {
                $byte |= 128;
            }

            $encoded .= chr($byte);
        } while ($length > 0);

        return $encoded;
    }
}
