/**
 * EventBus - Provides pub/sub functionality for loose coupling between modules
 * 
 * This module implements a simple event bus (publish/subscribe) pattern to allow
 * different modules to communicate without direct dependencies. This promotes
 * loose coupling and makes the system more maintainable and testable.
 * 
 * @module EventBus
 * @author MemberPress
 * @since 1.0.0
 */

/**
 * EventBus class - Implements the publish/subscribe pattern
 * 
 * @class
 */
class EventBus {
  /**
   * Creates a new EventBus instance
   * 
   * @constructor
   */
  constructor() {
    /**
     * Map of event names to arrays of subscribers
     * @type {Map<string, Array<{callback: Function, context: Object|null}>>}
     * @private
     */
    this._subscribers = new Map();
    
    /**
     * Map of subscriber IDs to event names and callbacks
     * @type {Map<string, {event: string, callback: Function}>}
     * @private
     */
    this._subscriptionsByID = new Map();
    
    /**
     * Counter for generating unique subscription IDs
     * @type {number}
     * @private
     */
    this._subscriptionCounter = 0;
  }

  /**
   * Subscribes to an event
   * 
   * @public
   * @param {string} event - Name of the event to subscribe to
   * @param {Function} callback - Function to call when the event is published
   * @param {Object} [context=null] - Context to bind the callback to
   * @returns {string} Subscription ID that can be used to unsubscribe
   */
  subscribe(event, callback, context = null) {
    if (typeof event !== 'string') {
      throw new Error('Event name must be a string');
    }
    
    if (typeof callback !== 'function') {
      throw new Error('Callback must be a function');
    }
    
    // Get or create the array of subscribers for this event
    if (!this._subscribers.has(event)) {
      this._subscribers.set(event, []);
    }
    
    // Generate a unique subscription ID
    const subscriptionId = this._generateSubscriptionId();
    
    // Add the subscriber to the event's subscriber list
    this._subscribers.get(event).push({ callback, context });
    
    // Store the subscription by ID for easy lookup when unsubscribing
    this._subscriptionsByID.set(subscriptionId, { event, callback });
    
    return subscriptionId;
  }

  /**
   * Unsubscribes from an event using the subscription ID
   * 
   * @public
   * @param {string} subscriptionId - ID returned from subscribe()
   * @returns {boolean} Whether the unsubscription was successful
   */
  unsubscribe(subscriptionId) {
    // Check if the subscription ID exists
    if (!this._subscriptionsByID.has(subscriptionId)) {
      return false;
    }
    
    // Get the subscription details
    const { event, callback } = this._subscriptionsByID.get(subscriptionId);
    
    // Check if the event exists in the subscribers map
    if (!this._subscribers.has(event)) {
      return false;
    }
    
    // Get the subscribers for this event
    const subscribers = this._subscribers.get(event);
    
    // Find the index of the subscriber with this callback
    const index = subscribers.findIndex(sub => sub.callback === callback);
    
    // If found, remove it
    if (index !== -1) {
      subscribers.splice(index, 1);
      
      // If there are no more subscribers for this event, remove the event
      if (subscribers.length === 0) {
        this._subscribers.delete(event);
      }
      
      // Remove the subscription from the subscriptions by ID map
      this._subscriptionsByID.delete(subscriptionId);
      
      return true;
    }
    
    return false;
  }

  /**
   * Publishes an event with data
   * 
   * @public
   * @param {string} event - Name of the event to publish
   * @param {*} data - Data to pass to subscribers
   * @returns {boolean} Whether the event had subscribers
   */
  publish(event, data) {
    // Check if the event exists and has subscribers
    if (!this._subscribers.has(event)) {
      return false;
    }
    
    // Get the subscribers for this event
    const subscribers = this._subscribers.get(event);
    
    // Call each subscriber's callback with the data
    subscribers.forEach(({ callback, context }) => {
      try {
        if (context) {
          callback.call(context, data);
        } else {
          callback(data);
        }
      } catch (error) {
        console.error(`Error in event subscriber for "${event}":`, error);
      }
    });
    
    return subscribers.length > 0;
  }

  /**
   * Alias for publish() method to maintain compatibility with different event bus APIs
   *
   * @public
   * @param {string} event - Name of the event to emit
   * @param {*} data - Data to pass to subscribers
   * @returns {boolean} Whether the event had subscribers
   */
  emit(event, data) {
    return this.publish(event, data);
  }

  /**
   * Alias for subscribe() method to maintain compatibility with Node.js EventEmitter API
   *
   * @public
   * @param {string} event - Name of the event to subscribe to
   * @param {Function} callback - Function to call when the event is published
   * @param {Object} [context=null] - Context to bind the callback to
   * @returns {string} Subscription ID that can be used to unsubscribe
   */
  on(event, callback, context = null) {
    return this.subscribe(event, callback, context);
  }

  /**
   * Subscribes to an event and automatically unsubscribes after it's published once
   *
   * @public
   * @param {string} event - Name of the event to subscribe to
   * @param {Function} callback - Function to call when the event is published
   * @param {Object} [context=null] - Context to bind the callback to
   * @returns {string} Subscription ID that can be used to unsubscribe
   */
  once(event, callback, context = null) {
    if (typeof event !== 'string') {
      throw new Error('Event name must be a string');
    }
    
    if (typeof callback !== 'function') {
      throw new Error('Callback must be a function');
    }
    
    // Create a wrapper function that will unsubscribe after being called
    const self = this;
    const wrapperCallback = function(data) {
      // Call the original callback
      if (context) {
        callback.call(context, data);
      } else {
        callback(data);
      }
      
      // Unsubscribe using the stored subscription ID
      self.unsubscribe(subscriptionId);
    };
    
    // Subscribe with the wrapper function
    const subscriptionId = this.subscribe(event, wrapperCallback, context);
    
    return subscriptionId;
  }

  /**
   * Unsubscribes all callbacks for a specific event
   * 
   * @public
   * @param {string} event - Name of the event to unsubscribe from
   * @returns {boolean} Whether the operation was successful
   */
  unsubscribeAll(event) {
    // Check if the event exists
    if (!this._subscribers.has(event)) {
      return false;
    }
    
    // Get all subscribers for this event
    const subscribers = this._subscribers.get(event);
    
    // Remove all subscriptions for this event from the subscriptions by ID map
    for (const [id, subscription] of this._subscriptionsByID.entries()) {
      if (subscription.event === event) {
        this._subscriptionsByID.delete(id);
      }
    }
    
    // Remove the event from the subscribers map
    this._subscribers.delete(event);
    
    return true;
  }

  /**
   * Clears all subscriptions
   * 
   * @public
   * @returns {void}
   */
  clear() {
    // Clear both maps
    this._subscribers.clear();
    this._subscriptionsByID.clear();
    
    // Reset the counter (optional, but might be useful for debugging)
    this._subscriptionCounter = 0;
  }

  /**
   * Gets the number of subscribers for an event
   * 
   * @public
   * @param {string} event - Name of the event
   * @returns {number} Number of subscribers
   */
  getSubscriberCount(event) {
    // If the event doesn't exist, return 0
    if (!this._subscribers.has(event)) {
      return 0;
    }
    
    // Return the number of subscribers for this event
    return this._subscribers.get(event).length;
  }

  /**
   * Checks if an event has subscribers
   * 
   * @public
   * @param {string} event - Name of the event
   * @returns {boolean} Whether the event has subscribers
   */
  hasSubscribers(event) {
    // Check if the event exists and has subscribers
    return this._subscribers.has(event) && this._subscribers.get(event).length > 0;
  }

  /**
   * Generates a unique subscription ID
   * 
   * @private
   * @returns {string} A unique subscription ID
   */
  _generateSubscriptionId() {
    // Increment the counter
    this._subscriptionCounter += 1;
    
    // Return a unique string ID
    return `sub_${this._subscriptionCounter}`;
  }
}

// Export the EventBus class
export default EventBus;