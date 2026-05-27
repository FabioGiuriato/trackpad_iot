<?php

use App\Http\Controllers\DeviceController;
use App\Support\SimpleMqttClient;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('mqtt:listen', function () {
    set_time_limit(0);

    $config = config('services.mqtt');

    foreach (['host', 'topic', 'username', 'password'] as $key) {
        if (blank($config[$key] ?? null)) {
            $this->error("MQTT_{$key} non configurato nel file .env.");

            return 1;
        }
    }

    $controller = app(DeviceController::class);

    while (true) {
        $client = new SimpleMqttClient(
            host: $config['host'],
            port: (int) $config['port'],
            username: $config['username'],
            password: $config['password'],
            clientId: 'trackpad-mqtt-' . bin2hex(random_bytes(4)),
            verifyTls: filter_var($config['tls_verify'] ?? false, FILTER_VALIDATE_BOOLEAN),
            keepAlive: 20,
        );

        try {
            $this->info('Connessione al broker MQTT...');
            $client->connect();
            $this->info('Connessione MQTT riuscita.');
            $client->subscribe($config['topic']);
            $this->info("In ascolto sul topic {$config['topic']}. Premi Ctrl+C per fermare.");

            $client->listen(function (string $topic, string $payload) use ($controller) {
                $this->line(now()->format('H:i:s') . " MQTT ricevuto su {$topic}: {$payload}");

                $response = $controller->storeEvent(Request::create('/iot/events', 'POST', [
                    'topic' => $topic,
                    'payload' => $payload,
                ]));

                if ($response->getStatusCode() >= 400) {
                    $this->warn(now()->format('H:i:s') . ' payload MQTT ignorato: formato non valido');
                    $this->warn($response->getContent());

                    return;
                }

                $this->line(now()->format('H:i:s') . ' ultimo stato ESP32 aggiornato');
            });
        } catch (Throwable $exception) {
            $client->disconnect();
            $this->warn(now()->format('H:i:s') . ' MQTT riconnessione tra 2 secondi: ' . $exception->getMessage());
            sleep(2);
        }
    }

    return 0;
})->purpose('Ascolta il topic MQTT HiveMQ e aggiorna l ultimo stato ESP32');

Artisan::command('mqtt:test', function () {
    $config = config('services.mqtt');

    foreach (['host', 'topic', 'username', 'password'] as $key) {
        if (blank($config[$key] ?? null)) {
            $this->error("MQTT_{$key} non configurato nel file .env.");

            return 1;
        }
    }

    $client = new SimpleMqttClient(
        host: $config['host'],
        port: (int) $config['port'],
        username: $config['username'],
        password: $config['password'],
        clientId: 'trackpad-mqtt-test-' . bin2hex(random_bytes(4)),
        verifyTls: filter_var($config['tls_verify'] ?? false, FILTER_VALIDATE_BOOLEAN),
        keepAlive: 20,
    );

    $payload = json_encode([
        'button1' => 0,
        'button2' => 0,
        'button3' => 0,
        'button4' => 0,
        'button5' => 0,
        'potenziometro' => 4095,
        'pot_percentuale' => 100,
        'joystick_x_valore' => 1936,
        'joystick_x_posizione' => 'CENTRO',
        'joystick_y_valore' => 1943,
        'joystick_y_posizione' => 'CENTRO',
        'joystick_click' => 'NON_PREMUTO',
    ]);

    try {
        $this->info('Connessione al broker MQTT...');
        $client->connect();
        $client->publish($config['topic'], $payload);
        $this->info("Payload di test pubblicato sul topic {$config['topic']}.");
        $this->line($payload);

        return 0;
    } catch (Throwable $exception) {
        $this->error('Test MQTT fallito: ' . $exception->getMessage());

        return 1;
    } finally {
        $client->disconnect();
    }
})->purpose('Pubblica un payload JSON di prova sul topic MQTT configurato');
