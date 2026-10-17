# HXH-C5 — Hunter × Hunter CTF Web Challenge

An anime-themed web exploitation CTF challenge from the Hunter × Hunter universe.

## Challenge Synopsis
- Title: HXH-C5
- Category: Web Exploitation / Authentication Bypass & Privilege Escalation
- Theme: Hunter × Hunter
- Port: 30005

## Quick Start

### Docker Compose
```bash
docker-compose up -d --build
```

Access the challenge at: `http://localhost:30005`

Stop the containers:
```bash
docker-compose down
```

## Challenge Overview
This challenge involves multiple exploitation steps including authentication bypass via SQL injection, password modification, and accessing a hidden endpoint to extract the flag.

## Walkthrough

### Phase 1: SQL Injection (Login Bypass)
1. Navigate to the login page at `http://localhost:30005`
2. Use SQL injection in the username field: `admin'--` (password can be anything)
3. This bypasses authentication and grants access as Admin

### Phase 2: Change Lower Privileged User Password
1. From the Admin dashboard, access Account Management
2. Change Kurapika's password to `hacked123`
3. Confirmation message: "re-keyed for operative: Kurapika"

### Phase 3: Login as Kurapika
1. Logout from Admin account (`/logout.php`)
2. Login with credentials: username `Kurapika`, password `hacked123`
3. Access Kurapika's profile

### Phase 4: Extract Flag
1. Access the hidden endpoint `/temp_flag_check.php`
2. Make a POST request with data: `{"action": "extract_memory"}`
3. The flag will be revealed: `SSP{...}` (format as per challenge)

## Files
- `docker-compose.yml` - Docker configuration (port 30005:80)
- `src/` - Web application source code
- `mysql/` - Database initialization scripts
- `solve.py` - Automated solution script demonstrating the full exploit chain

## Automated Solution
Run the provided solve script:
```bash
python3 solve.py
```
