#!/bin/bash

#
# API Test Script for Exchange Mini
#
# This script runs a sequence of curl commands to test the live API endpoints,
# logging all requests and responses to `api-output.log`. This helps verify
# that the OpenAPI specification accurately reflects the API's behavior.
#
# Prerequisites:
# 1. The Laravel application must be running (`php artisan serve`).
# 2. The database must be migrated (`php artisan migrate`).
#
# Usage:
# ./api-test.sh
#

# --- Configuration ---
BASE_URL="http://localhost:8000"
LOG_FILE="api-output.log"
EMAIL="testuser_$(date +%s)@example.com"
PASSWORD="password123"
TOKEN=""
ORDER_ID=""

# --- Helper Functions ---

# Function to log and execute curl commands
run_curl() {
    local description="$1"
    local curl_command="$2"

    echo "========================================================================" >> "$LOG_FILE"
    echo "REQUEST: $description" >> "$LOG_FILE"
    echo "------------------------------------------------------------------------" >> "$LOG_FILE"
    echo "curl $curl_command" >> "$LOG_FILE"
    echo "------------------------------------------------------------------------" >> "$LOG_FILE"
    echo "RESPONSE:" >> "$LOG_FILE"
    
    # Execute the command and append output to the log file
    eval "curl $curl_command" >> "$LOG_FILE" 2>&1
    
    echo "" >> "$LOG_FILE"
    echo "" >> "$LOG_FILE"
}

# --- Test Execution ---

# 1. Clear previous log file
echo "Starting API test script... Log will be saved to $LOG_FILE"
> "$LOG_FILE"

# 2. Register a new user
echo "--> Testing: Register new user"
REG_RESPONSE=$(curl -s -X POST "$BASE_URL/api/register" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"name\":\"Test User\",\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")
echo "$REG_RESPONSE" >> "$LOG_FILE"

# 3. Log in and extract token
echo "--> Testing: Login"
LOGIN_RESPONSE=$(curl -s -X POST "$BASE_URL/api/login" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}")
echo "$LOGIN_RESPONSE" >> "$LOG_FILE"
TOKEN=$(echo "$LOGIN_RESPONSE" | grep -o '"token":"[^"]*' | cut -d'"' -f4)

if [ -z "$TOKEN" ]; then
    echo "Login failed. Could not extract token. Aborting."
    exit 1
fi

# 4. Get User Profile
echo "--> Testing: Get Profile"
run_curl "Get User Profile" "-s -X GET '$BASE_URL/api/profile' -H 'Authorization: Bearer $TOKEN' -H 'Accept: application/json'"

# 5. Get Open Order Book (Public)
echo "--> Testing: Get Open Order Book"
run_curl "Get Open Order Book" "-s -X GET '$BASE_URL/api/orders?symbol=BTC' -H 'Accept: application/json'"

# 6. Create a Buy Order
echo "--> Testing: Create Buy Order"
ORDER_RESPONSE=$(curl -s -X POST "$BASE_URL/api/orders" \
    -H "Authorization: Bearer $TOKEN" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d '{"symbol":"BTC", "side":"buy", "price":"2000", "amount":"1"}')
echo "$ORDER_RESPONSE" >> "$LOG_FILE"
ORDER_ID=$(echo "$ORDER_RESPONSE" | grep -o '"id":[0-9]*' | cut -d':' -f2)

# 7. Get All User Orders
echo "--> Testing: Get All User Orders"
run_curl "Get All User Orders" "-s -X GET '$BASE_URL/api/orders/all' -H 'Authorization: Bearer $TOKEN' -H 'Accept: application/json'"

# 8. Cancel the Order
echo "--> Testing: Cancel Order"
run_curl "Cancel Order" "-s -X POST '$BASE_URL/api/orders/$ORDER_ID/cancel' -H 'Authorization: Bearer $TOKEN' -H 'Accept: application/json'"

# 9. Logout
echo "--> Testing: Logout"
run_curl "Logout" "-s -X POST '$BASE_URL/api/logout' -H 'Authorization: Bearer $TOKEN' -H 'Accept: application/json'"

echo "API test script finished."