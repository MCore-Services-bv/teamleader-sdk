# Error Codes Reference

Complete reference for all HTTP status codes and exceptions used by the Teamleader SDK.

## 📊 Quick Reference Table

| Code | Exception | Description | Retry? | Common Causes |
|------|-----------|-------------|--------|---------------|
| 400 | `TeamleaderException` | Bad Request | ❌ No | Invalid request format, missing required fields |
| 401 | `AuthenticationException` | Unauthorized | ❌ No | Invalid or expired credentials, missing token |
| 403 | `AuthorizationException` | Forbidden | ❌ No | Insufficient permissions, scope issues |
| 404 | `NotFoundException` | Not Found | ❌ No | Resource doesn't exist, wrong UUID |
| 422 | `ValidationException` | Validation Failed | ❌ No | Invalid field values, data type mismatch |
| 429 | `RateLimitExceededException` | Too Many Requests | ✅ Yes | Rate limit exceeded |
| 500 | `ServerException` | Internal Server Error | ✅ Yes | Teamleader server issue |
| 502 | `ServerException` | Bad Gateway | ✅ Yes | Teamleader gateway issue |
| 503 | `ServerException` | Service Unavailable | ✅ Yes | Teamleader maintenance or overload |
| 504 | `ServerException` | Gateway Timeout | ✅ Yes | Request timeout at gateway |
| 0 | `ConnectionException` | Connection Failed | ✅ Yes | Network issue, DNS failure |

## 🔴 Client Errors (4xx)

### 400 - Bad Request

**Exception:** `TeamleaderException`  
**Retry:** No  
**Description:** The request was malformed or contains invalid syntax.

**Common Causes:**
- Invalid JSON syntax
- Missing required parameters
- Incorrect parameter types
- Malformed date formats

**Example Response:**
```json
{
  "errors": [
    {
      "title": "Invalid JSON format"
    }
  ]
}
```

**Handling:**
```php
try {
    $company = Teamleader::companies()->create($data);
} catch (TeamleaderException $e) {
    if ($e->getCode() === 400) {
        Log::error('Bad request', [
            'errors' => $e->getAllErrors(),
            'data' => $data
        ]);
        
        // Fix the data structure and retry
    }
}
```

---

### 401 - Unauthorized

**Exception:** `AuthenticationException`  
**Retry:** No  
**Description:** Authentication failed or access token is invalid/expired.

**Common Causes:**
- Access token expired
- Invalid access token
- Access token revoked
- Missing Authorization header
- Failed token refresh

**Example Response:**
```json
{
  "error": "invalid_token",
  "error_description": "The access token provided is invalid"
}
```

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\AuthenticationException;

try {
    $companies = Teamleader::companies()->list();
} catch (AuthenticationException $e) {
    // Redirect to re-authenticate
    Log::warning('Authentication failed, redirecting to OAuth');
    return redirect()->route('teamleader.authorize');
}
```

**Prevention:**
- The SDK automatically refreshes tokens
- Use `Teamleader::isAuthenticated()` before making requests
- Handle authentication in middleware

---

### 403 - Forbidden

**Exception:** `AuthorizationException`  
**Retry:** No  
**Description:** Request was valid but server refuses to authorize it.

**Common Causes:**
- Insufficient OAuth scopes
- Account doesn't have feature enabled
- User lacks permission for the resource
- Attempting to modify read-only data

**Example Response:**
```json
{
  "errors": [
    {
      "title": "Insufficient permissions"
    }
  ]
}
```

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\AuthorizationException;

try {
    $invoice = Teamleader::invoices()->book($invoiceId);
} catch (AuthorizationException $e) {
    Log::warning('User lacks permission', [
        'user_id' => auth()->id(),
        'action' => 'book_invoice',
        'invoice_id' => $invoiceId
    ]);
    
    return response()->json([
        'error' => 'You do not have permission to book invoices'
    ], 403);
}
```

**Resolution:**
- Check OAuth scopes in your Teamleader app settings
- Verify user permissions in Teamleader
- Contact Teamleader support if feature should be available

---

### 404 - Not Found

**Exception:** `NotFoundException`  
**Retry:** No  
**Description:** The requested resource doesn't exist.

**Common Causes:**
- Incorrect UUID
- Resource was deleted
- Typo in endpoint or ID
- Wrong resource type for ID

**Example Response:**
```json
{
  "errors": [
    {
      "title": "Resource not found"
    }
  ]
}
```

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\NotFoundException;

try {
    $company = Teamleader::companies()->info($companyId);
} catch (NotFoundException $e) {
    Log::info('Company not found', ['id' => $companyId]);
    
    // Try to find by alternative identifier
    $companies = Teamleader::companies()->byVatNumber($vatNumber);
    
    if (empty($companies['data'])) {
        abort(404, 'Company not found');
    }
}
```

---

### 422 - Unprocessable Entity

**Exception:** `ValidationException`  
**Retry:** No  
**Description:** Request was well-formed but contains semantic errors.

**Common Causes:**
- Invalid field values
- Business rule violations
- Required field missing
- Field value out of range
- Invalid email format
- Invalid date format

**Example Response:**
```json
{
  "errors": [
    {
      "title": "Validation failed",
      "detail": {
        "email": ["The email field must be a valid email address"]
      }
    }
  ]
}
```

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\ValidationException;

try {
    $contact = Teamleader::contacts()->create($data);
} catch (ValidationException $e) {
    // Get all validation errors
    $errors = $e->getAllErrors();
    
    // Log for debugging
    Log::error('Validation failed', [
        'errors' => $errors,
        'data' => $data
    ]);
    
    // Return to user
    return back()->withErrors([
        'teamleader' => 'Validation failed: ' . implode(', ', $errors)
    ])->withInput();
}
```

**Prevention:**
- Validate data before sending to API
- Check required fields
- Validate email formats
- Validate date formats (ISO 8601)
- Check data types match API expectations

---

### 429 - Too Many Requests

**Exception:** `RateLimitExceededException`  
**Retry:** Yes (after delay)  
**Description:** Rate limit exceeded.

**Rate Limits:**
- **200 requests per minute** (sliding window)
- Headers indicate limit status
- SDK automatically throttles

**Response Headers:**
```
X-RateLimit-Limit: 200
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1634567890
Retry-After: 30
```

**Example Response:**
```json
{
  "errors": [
    {
      "title": "Rate limit exceeded"
    }
  ]
}
```

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\RateLimitExceededException;

try {
    $companies = Teamleader::companies()->list();
} catch (RateLimitExceededException $e) {
    $retryAfter = $e->getRetryAfter(); // seconds
    $resetTime = $e->getResetTime(); // Unix timestamp
    
    Log::warning('Rate limit exceeded', [
        'retry_after' => $retryAfter,
        'reset_time' => date('Y-m-d H:i:s', $resetTime)
    ]);
    
    // Wait and retry
    sleep($retryAfter);
    return Teamleader::companies()->list();
}
```

**Prevention:**
The SDK automatically handles rate limiting:
- Monitors request count
- Applies throttling at 70% capacity
- Respects `Retry-After` headers
- Implements exponential backoff

Check rate limit status:
```php
$stats = Teamleader::getRateLimitStats();

if ($stats['usage_percentage'] > 80) {
    Log::warning('Approaching rate limit', $stats);
}
```

## 🔴 Server Errors (5xx)

### 500 - Internal Server Error

**Exception:** `ServerException`  
**Retry:** Yes (with exponential backoff)  
**Description:** Teamleader server encountered an error.

**Common Causes:**
- Teamleader API bug
- Database issue on Teamleader side
- Unexpected error in Teamleader's code

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\ServerException;

try {
    $deal = Teamleader::deals()->create($data);
} catch (ServerException $e) {
    Log::critical('Teamleader server error', [
        'status_code' => $e->getCode(),
        'message' => $e->getMessage()
    ]);
    
    // The SDK automatically retries
    // If retries exhausted, queue for later
    dispatch(new CreateDealJob($data))->delay(now()->addMinutes(5));
}
```

---

### 502 - Bad Gateway

**Exception:** `ServerException`  
**Retry:** Yes  
**Description:** Teamleader gateway received invalid response.

**Common Causes:**
- Teamleader backend server issue
- Load balancer problem
- Gateway configuration issue

---

### 503 - Service Unavailable

**Exception:** `ServerException`  
**Retry:** Yes  
**Description:** Teamleader service temporarily unavailable.

**Common Causes:**
- Scheduled maintenance
- Temporary overload
- Deployment in progress

**Handling:**
```php
try {
    $result = Teamleader::invoices()->list();
} catch (ServerException $e) {
    if ($e->getCode() === 503) {
        // Service temporarily unavailable
        // Schedule for later processing
        Cache::put('teamleader_unavailable', true, 300);
        
        return response()->json([
            'message' => 'Teamleader is temporarily unavailable. Please try again in a few minutes.'
        ], 503);
    }
}
```

---

### 504 - Gateway Timeout

**Exception:** `ServerException`  
**Retry:** Yes  
**Description:** Gateway timeout waiting for Teamleader response.

**Common Causes:**
- Request took too long to process
- Network latency
- Large dataset processing

## 🔌 Connection Errors

### Connection Failed

**Exception:** `ConnectionException`  
**Retry:** Yes  
**Description:** Failed to establish connection to Teamleader.

**Common Causes:**
- Network connectivity issues
- DNS resolution failure
- Firewall blocking requests
- SSL/TLS handshake failure

**Handling:**
```php
use McoreServices\TeamleaderSDK\Exceptions\ConnectionException;

try {
    $companies = Teamleader::companies()->list();
} catch (ConnectionException $e) {
    Log::error('Connection failed', [
        'message' => $e->getMessage(),
        'context' => $e->getContext()
    ]);
    
    // Check network connectivity
    // Notify monitoring system
    // Queue for retry
}
```

## 🎯 Best Practices

### Error Handling Strategy

```php
use McoreServices\TeamleaderSDK\Exceptions\{
    AuthenticationException,
    ValidationException,
    RateLimitExceededException,
    ServerException,
    TeamleaderException
};

try {
    $result = Teamleader::companies()->create($data);
    
} catch (ValidationException $e) {
    // User error - show validation messages
    return back()->withErrors($e->getAllErrors());
    
} catch (AuthenticationException $e) {
    // Auth issue - redirect to reconnect
    return redirect()->route('teamleader.authorize');
    
} catch (RateLimitExceededException $e) {
    // Rate limit - wait and retry or queue
    dispatch(new ProcessLater($data))->delay($e->getRetryAfter());
    
} catch (ServerException $e) {
    // Server issue - SDK auto-retries, then queue
    Log::critical('Server error after retries', ['error' => $e->getMessage()]);
    dispatch(new ProcessLater($data))->delay(now()->addMinutes(5));
    
} catch (TeamleaderException $e) {
    // General error - log and notify
    Log::error('Teamleader API error', [
        'code' => $e->getCode(),
        'message' => $e->getMessage()
    ]);
}
```

### Retry Logic

The SDK implements automatic retry with exponential backoff for:
- `ServerException` (500, 502, 503, 504)
- `RateLimitExceededException` (429)
- `ConnectionException`

**Default retry configuration:**
```php
'api' => [
    'retry_attempts' => 3,
    'retry_delay' => 1000, // Base delay in milliseconds
]
```

**Exponential backoff formula:**
```
delay = min(baseDelay * (2 ^ attempt) + jitter, maxDelay)
```

### Logging Strategy

```php
// Critical errors (requires immediate attention)
Log::critical('Authentication completely failed');

// Errors (abnormal conditions)
Log::error('API request failed after retries', $context);

// Warnings (unusual but handled)
Log::warning('Rate limit approaching', $stats);

// Info (notable events)
Log::info('Resource created successfully', ['id' => $id]);

// Debug (detailed information)
Log::debug('API request details', $requestData);
```

### Monitoring

Track these metrics:
- Authentication failures
- Rate limit hits
- Server error frequency
- Average response times
- Retry success rates

```php
// Example monitoring
$stats = Teamleader::getRateLimitStats();

if ($stats['usage_percentage'] > 90) {
    // Alert monitoring system
    alert('Teamleader rate limit critical');
}
```

## 🆘 Getting Help

If you encounter errors not covered here:

1. **Check Logs**: Review Laravel logs for detailed error information
2. **SDK Status**: Run `php artisan teamleader:health`
3. **Teamleader Status**: Check [status.teamleader.eu](https://status.teamleader.eu)
4. **Documentation**: See [official API docs](https://developer.focus.teamleader.eu/)
5. **GitHub Issues**: Search or create an issue
6. **Support**: Contact help@mcore-services.be

---

**Last Updated:** October 2024  
**SDK Version:** 1.0.0
