# INFERNOX API Documentation

**Base URL:** `http://localhost:8000/api`  
**WebSocket:** `ws://localhost:8081`  
**Version:** 1.0.0

---

## Authentication

All protected endpoints require a Bearer token in the header:
```
Authorization: Bearer YOUR_TOKEN_HERE
Accept: application/json
Content-Type: application/json
```

There are two token types:
- **Operator token** — issued on login, used by the Next.js dashboard
- **Robot token** — issued on provisioning, used by the physical robot

---

## Public Endpoints

### Health Check
```
GET /api/health
```
No auth required. Returns system status.

**Response:**
```json
{
  "status": "ok",
  "service": "INFERNOX API",
  "version": "1.0.0",
  "checks": {
    "database": "connected",
    "redis": "connected"
  },
  "timestamp": "2026-07-15T09:00:00+00:00"
}
```

---

## Auth Endpoints (Operators)

### Login
```
POST /api/auth/login
```
**Body:**
```json
{
  "email": "admin@infernox.com",
  "password": "password123"
}
```
**Response:**
```json
{
  "message": "Login successful.",
  "token": "1|abc123...",
  "user": {
    "id": 1,
    "name": "INFERNOX Admin",
    "email": "admin@infernox.com",
    "role": "super_admin",
    "permissions": ["robots.view", "robots.create", "..."]
  }
}
```

### Get Current User
```
GET /api/auth/me
Authorization: Bearer TOKEN
```

### Logout
```
POST /api/auth/logout
Authorization: Bearer TOKEN
```

### Register New Operator (super_admin only)
```
POST /api/auth/register
Authorization: Bearer TOKEN
```
**Body:**
```json
{
  "name": "New Operator",
  "email": "operator@infernox.com",
  "password": "password123",
  "password_confirmation": "password123",
  "role": "operator"
}
```
Roles: `operator` or `viewer`

---

## Robot Endpoints (Operators)

### List Robots
```
GET /api/robots
Authorization: Bearer TOKEN
```
**Query params:**
- `status` — filter by status (online, offline, idle, active, error)
- `search` — search by name or serial number
- `low_battery` — boolean, filter low battery robots
- `per_page` — results per page (default 20)

### Provision New Robot (super_admin only)
```
POST /api/robots
Authorization: Bearer TOKEN
```
**Body:**
```json
{
  "serial_number": "INFERNOX-001",
  "name": "Unit Alpha",
  "model": "INFERNOX-MK1"
}
```
**Response includes `api_token`** — save it immediately, shown only once.

### Get Single Robot
```
GET /api/robots/{id}
Authorization: Bearer TOKEN
```

### Update Robot
```
PUT /api/robots/{id}
Authorization: Bearer TOKEN
```
**Body:**
```json
{
  "name": "Unit Alpha Updated",
  "model": "INFERNOX-MK2"
}
```

### Decommission Robot (super_admin only)
```
DELETE /api/robots/{id}
Authorization: Bearer TOKEN
```

### Rotate Robot Token (super_admin only)
```
POST /api/robots/{id}/rotate-token
Authorization: Bearer TOKEN
```

---

## Incident Endpoints (Operators)

Incidents are created **automatically** when fire is detected from telemetry.
Operators manage them via these endpoints.

### List Incidents
```
GET /api/incidents
Authorization: Bearer TOKEN
```
**Query params:**
- `status` — open, investigating, suppressing, resolved, false_alarm
- `severity` — low, medium, high, critical
- `robot_id` — filter by robot
- `active` — boolean, only active incidents

### Get Single Incident
```
GET /api/incidents/{id}
Authorization: Bearer TOKEN
```
Returns full incident with robot details, operator updates, and commands.

### Update Incident
```
PUT /api/incidents/{id}
Authorization: Bearer TOKEN
```
**Body:**
```json
{
  "status": "investigating",
  "severity": "high",
  "fire_type": "class_c"
}
```
**Status values:** `open` → `investigating` → `suppressing` → `resolved` or `false_alarm`

**Fire type values:**
| Value | Description | Extinguisher |
|-------|-------------|--------------|
| class_a | Ordinary combustibles | Water, Foam, Dry Powder |
| class_b | Flammable liquids | Foam, CO2, Dry Powder |
| class_c | Electrical equipment | CO2 ONLY — never water |
| class_d | Flammable metals | Specialist Dry Powder |
| class_f | Cooking oils | Wet Chemical |
| unknown | Unclassified | Assess before selecting |

### Add Incident Update (operator note)
```
POST /api/incidents/{id}/updates
Authorization: Bearer TOKEN
```
**Body:**
```json
{
  "note": "Dispatched Unit Alpha to sector 4",
  "action_taken": "dispatched"
}
```
**Action values:** `acknowledged`, `dispatched`, `suppressed`, `investigated`, `escalated`, `resolved`, `false_alarm`

---

## Command Endpoints (Operators)

### Send Command to Robot
```
POST /api/robots/{robot_id}/commands
Authorization: Bearer TOKEN
```
**Body:**
```json
{
  "command_type": "suppress",
  "payload": {}
}
```

**Available commands:**

| Command | Payload | Description |
|---------|---------|-------------|
| move_to | `{"lat": -4.04, "lng": 39.66}` | Move robot to coordinates |
| suppress | `{}` | Activate fire suppression |
| return_home | `{}` | Return robot to base |
| activate_siren | `{}` | Activate warning siren |
| stop | `{}` | Emergency stop |

### List Robot Commands
```
GET /api/robots/{robot_id}/commands
Authorization: Bearer TOKEN
```

---

## Robot Endpoints (Robot token only)

These endpoints are called by the **physical robot**, not the dashboard.
The robot uses its provisioned API token.

### Submit Telemetry
```
POST /api/robot/telemetry
Authorization: Bearer ROBOT_TOKEN
```
**Body:**
```json
{
  "gps": {
    "lat": -4.0435,
    "lng": 39.6682
  },
  "battery": 87,
  "temperature": 28.5,
  "smoke_level": 12.0,
  "fire_detected": false,
  "timestamp": "2026-07-15T09:00:00+00:00",
  "co_level": null,
  "gas_type": null,
  "smoke_color": null
}
```
**Response:** `202 Accepted` — data queued for processing.

**Optional extra sensor fields (for fire classification):**
- `co_level` — carbon monoxide level in ppm
- `gas_type` — `lpg`, `propane`, etc.
- `smoke_color` — `white`, `black`, `gray`

### Get Pending Commands
```
GET /api/robot/commands/pending
Authorization: Bearer ROBOT_TOKEN
```
Robot polls this every 5 seconds. Returns pending commands and marks them as `sent`.

### Acknowledge Command
```
PATCH /api/robot/commands/{command_id}/acknowledge
Authorization: Bearer ROBOT_TOKEN
```
**Body:**
```json
{
  "status": "executed"
}
```
**Status values:** `acknowledged`, `executed`, `failed`

---

## WebSocket Events (Real-time)

Connect using Pusher JS client pointing to Reverb:

```javascript
const pusher = new Pusher('infernox-key', {
  wsHost: 'localhost',
  wsPort: 8081,
  forceTLS: false,
  cluster: '',
  authEndpoint: 'http://localhost:8000/broadcasting/auth',
  auth: {
    headers: {
      Authorization: `Bearer ${token}`,
      Accept: 'application/json',
    }
  }
});
```

### Channels

**`private-operations.dashboard`** — all operators
**`private-robot.{robot_id}`** — specific robot monitoring

### Events

| Event | Channel | Payload |
|-------|---------|---------|
| `telemetry.received` | robot + dashboard | Latest telemetry reading |
| `incident.opened` | robot + dashboard | New incident details |
| `incident.updated` | robot + dashboard | Updated incident |
| `command.dispatched` | robot + dashboard | Command sent to robot |
| `robot.status_changed` | dashboard | Robot online/offline |

**Example — listening for telemetry:**
```javascript
const channel = pusher.subscribe('private-operations.dashboard');

channel.bind('telemetry.received', (data) => {
  console.log('New telemetry:', data.reading);
  console.log('Robot ID:', data.robot_id);
});

channel.bind('incident.opened', (data) => {
  console.log('FIRE DETECTED:', data.incident);
  // Show alert on dashboard
});

channel.bind('command.dispatched', (data) => {
  console.log('Command sent:', data.command);
});
```

---

## User Roles

| Role | Can Do |
|------|--------|
| super_admin | Everything including provisioning robots and managing users |
| operator | View robots, send commands, manage incidents |
| viewer | Read-only access to everything |

---

## Default Test Accounts

| Email | Password | Role |
|-------|----------|------|
| admin@infernox.com | password123 | super_admin |
| operator@infernox.com | password123 | operator |

---

## Test Robot

| Field | Value |
|-------|-------|
| Serial | INFERNOX-001 |
| Name | Unit Alpha |
| Model | INFERNOX-MK1 |

Robot token was issued on provisioning — check your team's secure notes.

---

## Fire Detection Thresholds

The system automatically opens an incident when:
- Temperature exceeds **200°C**
- Smoke level exceeds **500 ppm**
- Robot reports `fire_detected: true`

These thresholds are configurable in `config/infernox.php`.