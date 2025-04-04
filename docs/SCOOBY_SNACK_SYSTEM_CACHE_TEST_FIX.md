# System Information Caching Test Fix

## Problem
The System Information Caching test in the diagnostic panel was failing with the error:
```
Test Failed
System Information Cache Test failed: Method MPAI_System_Cache::persist_to_filesystem() does not exist
```

Additionally, even after fixing the method name errors, two specific tests continue to fail:
1. The Cache Expiration test
2. The Cache Invalidation test

## Root Cause
### Method Name Errors
The test in `direct-ajax-handler.php` was attempting to call methods that don't exist in the `MPAI_System_Cache` class:

1. `persist_to_filesystem()` - This method doesn't exist. Instead, the class uses `set_in_filesystem()` to persist cache data to the filesystem.
2. `load_from_filesystem()` - This method doesn't exist. Instead, the class uses `get_from_filesystem()` for individual items and `maybe_load_filesystem_cache()` to load all cached items.

The test was attempting to use reflection to access these non-existent methods, which caused PHP to throw exceptions.

### Ongoing Test Failures
After fixing the method name issues, the tests still report failures in:

1. **Cache Expiration Test**: This test modifies the TTL to 1 second, waits 2 seconds, and then checks if the entry has expired. The test is failing, suggesting that entries are not being properly expired after their TTL.

2. **Cache Invalidation Test**: This test attempts to invalidate cache entries by calling `invalidate_plugin_cache()`, but the entries appear to persist after invalidation.

## Solution Implemented
### Method Name Fix
1. Replaced the call to the non-existent `persist_to_filesystem()` method with a simple call to `set()`, which already handles filesystem persistence internally:
   ```php
   // Don't need to force persistence - it happens automatically in set()
   // The method 'persist_to_filesystem' doesn't exist, it's actually called 'set_in_filesystem'
   // Just call set() again to ensure it's in the filesystem
   $system_cache->set($persist_key, $persist_data, 'default');
   ```

2. Replaced the call to the non-existent `load_from_filesystem()` method with the existing `maybe_load_filesystem_cache()` method:
   ```php
   // Try to load from filesystem
   // The method 'load_from_filesystem' doesn't exist, but 'get_from_filesystem' does
   // However, we can just call 'maybe_load_filesystem_cache()' instead
   $load_method = $reflection->getMethod('maybe_load_filesystem_cache');
   $load_method->setAccessible(true);
   $load_method->invoke($system_cache);
   ```

### Ongoing Test Failures
The Cache Expiration and Cache Invalidation tests continue to fail even after fixing the method name issues. This suggests potential issues with how the cache system implements expiration and invalidation. These issues are documented here but have not been fixed in this Scooby Snack, as they require deeper investigation into the cache implementation.

## Why It Works
- The method name fix resolves the immediate errors by using methods that actually exist in the class.
- The `MPAI_System_Cache::set()` method already handles filesystem persistence by calling `set_in_filesystem()` internally.
- The `maybe_load_filesystem_cache()` method correctly loads cached items from the filesystem.
- While this doesn't fix the expiration and invalidation failures, it allows the tests to run without throwing exceptions, giving more accurate test results.

## Lessons Learned
1. When using reflection to access private methods, always verify that the methods actually exist in the class.
2. When writing tests, it's preferable to test the public API rather than internal implementation details where possible.
3. Tests that rely on specific method names can become brittle if the implementation changes.
4. Documentation and implementation should stay in sync when refactoring method names.
5. Cache expiration and invalidation are complex mechanisms that require careful testing and implementation.
6. Test failures can be valuable indicators of potential issues in the underlying code, not just in the tests themselves.

## Additional Recommendations
1. Consider adding PHPDoc comments to the `MPAI_System_Cache` class that clearly document all public and private methods.
2. Update any existing documentation that might reference these incorrect method names.
3. Consider implementing more tests that use the public API rather than using reflection to access private methods.
4. Investigate why the Cache Expiration test is failing - check if TTL values are being properly applied during retrieval.
5. Review the Cache Invalidation implementation to ensure it's properly removing targeted cache entries.
6. Consider adding a specific issue in the development tracker to address these persistent test failures in a future update.