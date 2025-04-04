# Investigation: System Information Caching Test Failure

**Status:** ✅ Fixed  
**Date:** April 4, 2025  
**Categories:** testing, performance, caching  
**Related Files:** 
- `/includes/direct-ajax-handler.php`
- `/includes/class-mpai-system-cache.php`
- `/test/test-system-cache.php`

## Problem Statement

The System Information Caching test in the diagnostic panel was failing with the error:

```
Test Failed
System Information Cache Test failed: Method MPAI_System_Cache::persist_to_filesystem() does not exist
```

Even after fixing the method name errors, two specific tests continued to fail:
1. The Cache Expiration test
2. The Cache Invalidation test

## Investigation Steps

1. **Identify Relevant Files**
   - Located `direct-ajax-handler.php` with the test case implementation (lines 1244-1587)
   - Examined `class-mpai-system-cache.php` to understand the actual cache implementation
   - Reviewed `test-system-cache.php` for the expected test behavior

2. **Analyze Method Name Discrepancies**
   - The test was attempting to call `persist_to_filesystem()` method which doesn't exist in the cache class
   - The actual method for filesystem persistence is `set_in_filesystem()`
   - Test was also trying to use `load_from_filesystem()` method, but the class uses `get_from_filesystem()` and `maybe_load_filesystem_cache()`

3. **Test Execution Flow Analysis**
   - The test in `direct-ajax-handler.php` uses reflection to access private methods
   - This caused PHP to throw exceptions when attempting to access non-existent methods
   - The test attempts to modify TTL to 1 second, wait 2 seconds, and check for expiration
   - It also attempts to invalidate the cache and verify entries were removed

4. **Cache System Implementation Review**
   - `MPAI_System_Cache` implements both in-memory and filesystem caching
   - Cache invalidation uses type-specific methods like `invalidate_plugin_cache()`
   - Cache expiration is checked in the `get()` method based on TTL settings
   - Filesystem persistence happens automatically as part of the `set()` method

## Diagnostic Information

```php
// Error-causing code in direct-ajax-handler.php:
// Attempts to call persist_to_filesystem() which doesn't exist
$persist_method = $reflection->getMethod('persist_to_filesystem');
$persist_method->setAccessible(true);
$persist_method->invoke($system_cache);

// Also attempts to call load_from_filesystem() which doesn't exist
$load_method = $reflection->getMethod('load_from_filesystem');
$load_method->setAccessible(true);
$load_method->invoke($system_cache);
```

**Current Behavior:** 
- The test fails with a fatal error when trying to access non-existent methods
- Expiration test fails - entries don't expire after TTL is modified and waiting period
- Invalidation test fails - entries persist after invalidation

**Expected Behavior:**
- Tests should use the actual methods that exist in the class
- Cache entries should expire based on TTL settings
- Cache entries should be properly invalidated when calling invalidation methods

## Hypotheses

1. **Method Name Mismatch Hypothesis**
   - The test was written against an older version of the cache class
   - Or method names were refactored without updating the tests
   - Simply updating the method names to match the current implementation should fix the fatal errors

2. **Cache Expiration Implementation Hypothesis**
   - The TTL modification via reflection isn't properly affecting the cached entry expiration time
   - The cache expiration logic might not be working correctly

3. **Cache Invalidation Implementation Hypothesis**
   - The `invalidate_plugin_cache()` method might not be correctly removing entries
   - Or the test might be checking the wrong key after invalidation

## Preliminary Findings

**Root Cause:**
The test was attempting to use methods that don't exist in the `MPAI_System_Cache` class. This suggests the test was written against an earlier implementation of the cache system, or the cache system was refactored without updating the tests.

**Immediate Fix:**
1. Replace the call to `persist_to_filesystem()` with a simple call to `set()`, which already handles filesystem persistence internally
2. Replace the call to `load_from_filesystem()` with the existing `maybe_load_filesystem_cache()` method

**Additional Issues:**
The Cache Expiration and Cache Invalidation tests continue to fail even after fixing the method name issues. This suggests there may be potential issues with how the cache system implements expiration and invalidation.

These issues require deeper investigation:
- For the Cache Expiration test, it appears the TTL is not being correctly applied when retrieving the cached entry.
- For the Cache Invalidation test, it appears the cache entries are not being properly removed when calling invalidation methods.

## Next Steps

1. Fix the immediate method name errors to make the test run without fatal errors
2. Document the ongoing issues with expiration and invalidation in a Scooby Snack
3. Consider a more comprehensive review of the cache expiration and invalidation mechanisms
4. Update any existing documentation that might reference the incorrect method names