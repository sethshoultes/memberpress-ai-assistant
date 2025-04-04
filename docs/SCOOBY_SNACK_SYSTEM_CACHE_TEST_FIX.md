# System Information Caching Test Fix

## Problem
The System Information Caching test in the diagnostic panel was failing with the error:
```
Test Failed
System Information Cache Test failed: Method MPAI_System_Cache::persist_to_filesystem() does not exist
```

## Root Cause
The test in `direct-ajax-handler.php` was attempting to call methods that don't exist in the `MPAI_System_Cache` class:

1. `persist_to_filesystem()` - This method doesn't exist. Instead, the class uses `set_in_filesystem()` to persist cache data to the filesystem.
2. `load_from_filesystem()` - This method doesn't exist. Instead, the class uses `get_from_filesystem()` for individual items and `maybe_load_filesystem_cache()` to load all cached items.

The test was attempting to use reflection to access these non-existent methods, which caused PHP to throw exceptions.

## Solution Implemented
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

## Why It Works
- The `MPAI_System_Cache::set()` method already handles filesystem persistence by calling `set_in_filesystem()` internally, so we don't need to call the filesystem method directly.
- The `maybe_load_filesystem_cache()` method is designed to load cached items from the filesystem, which is exactly what the test was trying to do.
- The fix maintains the test's intent (verifying filesystem persistence) while using the methods that actually exist in the class.

## Lessons Learned
1. When using reflection to access private methods, always verify that the methods actually exist in the class.
2. When writing tests, it's preferable to test the public API rather than internal implementation details where possible.
3. Tests that rely on specific method names can become brittle if the implementation changes.
4. Documentation and implementation should stay in sync when refactoring method names.

## Additional Recommendations
1. Consider adding PHPDoc comments to the `MPAI_System_Cache` class that clearly document all public and private methods.
2. Update any existing documentation that might reference these incorrect method names.
3. Consider implementing more tests that use the public API rather than using reflection to access private methods.