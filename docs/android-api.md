# Trackpad MQTT Android API

Base URL in sviluppo:

```text
http://127.0.0.1:8000/api/v1
```

Da emulatore Android usa:

```text
http://10.0.2.2:8000/api/v1
```

Da telefono fisico usa l'IP del PC nella stessa rete, per esempio:

```text
http://192.168.1.50:8000/api/v1
```

## Auth

### Register

`POST /register`

```json
{
  "username": "mattia",
  "email": "mattia@example.com",
  "password": "Password123",
  "password_confirmation": "Password123",
  "device_name": "android"
}
```

### Login

`POST /login`

```json
{
  "email": "mattia@example.com",
  "password": "Password123",
  "device_name": "android"
}
```

Risposta:

```json
{
  "token_type": "Bearer",
  "access_token": "tp_...",
  "expires_at": "2026-06-26T10:00:00.000000Z",
  "user": {
    "id": 1,
    "username": "mattia",
    "email": "mattia@example.com"
  }
}
```

Android deve salvare `access_token` e inviarlo nelle chiamate protette:

```text
Authorization: Bearer tp_...
```

### Logout

`POST /logout`

Header:

```text
Authorization: Bearer tp_...
```

Il token viene eliminato dal database e non funziona piu.

## Dati App

Tutte queste rotte richiedono `Authorization: Bearer`.

### Utente corrente

`GET /me`

### Lista canzoni

`GET /songs`

Risposta:

```json
{
  "songs": [
    {
      "id": 1,
      "title": "Prima canzone",
      "bpm": 120,
      "events_count": 16,
      "created_at": "...",
      "updated_at": "..."
    }
  ]
}
```

### Dettaglio canzone e channel rack

`GET /songs/{id}`

La risposta contiene:

- `song.step_count`: numero di step da disegnare.
- `song.channels`: righe del channel rack.
- `channel.steps`: step attivi da colorare.
- `sound.tipo`, `sound.nome_tipo`, `sound.type_label`: categoria del suono.

### Ultimo stato MQTT

`GET /mqtt/latest`

Risposta:

```json
{
  "event": {
    "button1": 0,
    "button2": 0,
    "button3": 0,
    "button4": 0,
    "button5": 0,
    "potenziometro": 3145,
    "pot_percentuale": 76,
    "joystick_x_posizione": "CENTRO",
    "joystick_click": "NON_PREMUTO"
  }
}
```

### Tipi suoni

`GET /types`

```json
{
  "types": [
    { "tipo": 0, "label": "Trap", "nome_tipo": "trep" },
    { "tipo": 1, "label": "Drill", "nome_tipo": "drill" },
    { "tipo": 2, "label": "Reggaeton", "nome_tipo": "reggaeton" },
    { "tipo": 3, "label": "Troll", "nome_tipo": "troll" }
  ]
}
```
